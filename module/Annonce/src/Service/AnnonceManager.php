<?php
namespace Annonce\Service;

use Annonce\Entity\Annonce;
use Annonce\Entity\AnnonceTag;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use User\Entity\User;

/**
 * Cycle de vie des annonces : creation, modification, renouvellement,
 * retrait et purge (suppression definitive avec les photos).
 */
class AnnonceManager
{
    private EntityManager $entityManager;
    private AnnonceSettings $settings;
    private PhotoStorage $photoStorage;

    public function __construct(EntityManager $entityManager, AnnonceSettings $settings, PhotoStorage $photoStorage)
    {
        $this->entityManager = $entityManager;
        $this->settings = $settings;
        $this->photoStorage = $photoStorage;
    }

    /**
     * @return AnnonceTag[] tags proposes, dans l'ordre d'affichage
     */
    public function activeTags(): array
    {
        return $this->entityManager->getRepository(AnnonceTag::class)->findBy(['isActive' => true], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function countOnline(User $user): int
    {
        return $this->entityManager->getRepository(Annonce::class)->countOnlineByOwner($user);
    }

    public function hasReachedQuota(User $user): bool
    {
        return $this->countOnline($user) >= $this->settings->getInt(AnnonceSettings::MAX_ONLINE);
    }

    /**
     * Cree ($annonce null) ou modifie une annonce depuis le formulaire.
     *
     * @param array $input     champs POST bruts : title, description, price, free, tags[], remove_photos[]
     * @param array $uploads   fichiers envoyes (liste d'entrees $_FILES normalisees)
     * @return array{annonce: ?Annonce, errors: string[]}
     */
    public function save(?Annonce $annonce, User $user, array $input, array $uploads): array
    {
        $title = mb_substr(InputSanitizer::cleanString($input['title'] ?? ''), 0, 200);
        $description = InputSanitizer::cleanText($input['description'] ?? '');
        $free = !empty($input['free']);
        $priceRaw = trim((string) ($input['price'] ?? ''));
        $tagIds = array_values(array_unique(array_map('intval', (array) ($input['tags'] ?? []))));
        $removeIds = array_map('intval', (array) ($input['remove_photos'] ?? []));
        $uploads = array_values(array_filter($uploads, fn ($file) => is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));

        $errors = [];
        // Texte mal encode (navigateur ou outil exotique) : refuse proprement plutot qu'une erreur base de donnees
        if (!mb_check_encoding($title . $description, 'UTF-8')) {
            return ['annonce' => $annonce, 'errors' => ['Le texte contient des caractères non reconnus.']];
        }
        if (mb_strlen($title) < 3 || mb_strlen($title) > Annonce::TITLE_MAX) {
            $errors[] = 'Le titre doit faire entre 3 et ' . Annonce::TITLE_MAX . ' caractères.';
        }
        if (mb_strlen($description) < 10 || mb_strlen($description) > Annonce::DESCRIPTION_MAX) {
            $errors[] = 'La description doit faire entre 10 et ' . Annonce::DESCRIPTION_MAX . ' caractères.';
        }
        $price = null;
        if (!$free) {
            if ($priceRaw === '' || !ctype_digit($priceRaw) || (int) $priceRaw > Annonce::PRICE_MAX) {
                $errors[] = 'Indiquez un prix en euros (nombre entier), ou cochez « À donner ».';
            } else {
                $price = (int) $priceRaw;
            }
        }

        // Tags : parmi les tags actifs, ou deja poses sur l'annonce (tag desactive depuis)
        $tags = [];
        foreach ($tagIds as $tagId) {
            $tag = $this->entityManager->getRepository(AnnonceTag::class)->find($tagId);
            if ($tag && ($tag->isActive() || ($annonce && $annonce->hasTag($tag)))) {
                $tags[] = $tag;
            }
        }
        if (count($tags) > Annonce::TAGS_MAX) {
            $errors[] = 'Choisissez au plus ' . Annonce::TAGS_MAX . ' catégories.';
        } elseif (!$tags && $this->activeTags()) {
            $errors[] = 'Choisissez au moins une catégorie.';
        }

        $removed = [];
        if ($annonce) {
            foreach ($annonce->getPhotos() as $photo) {
                if (in_array($photo->getId(), $removeIds, true)) {
                    $removed[] = $photo;
                }
            }
        }
        $photoCount = ($annonce ? $annonce->getPhotos()->count() : 0) - count($removed) + count($uploads);
        if ($photoCount > Annonce::PHOTOS_MAX) {
            $errors[] = Annonce::PHOTOS_MAX . ' photos maximum.';
        } elseif ($photoCount < 1) {
            $errors[] = 'Ajoutez au moins une photo.';
        }

        if ($errors) {
            return ['annonce' => $annonce, 'errors' => $errors];
        }

        $isNew = $annonce === null;
        if ($isNew) {
            // Enregistree d'abord : le dossier des photos porte son identifiant
            $annonce = new Annonce($user, $this->newExpiry());
            $annonce->update($title, $description, $price, $tags);
            $this->entityManager->persist($annonce);
            $this->entityManager->flush();
        }

        // Photos envoyees avant toute autre modification : en cas d'echec, rien ne change
        $stored = [];
        try {
            foreach ($uploads as $file) {
                $stored[] = $this->photoStorage->store($annonce->getId(), $file);
            }
        } catch (\RuntimeException $e) {
            foreach ($stored as $filename) {
                $this->photoStorage->delete($annonce->getId(), $filename);
            }
            if ($isNew) {
                $this->photoStorage->deleteAll($annonce->getId());
                $this->entityManager->remove($annonce);
                $this->entityManager->flush();
                $annonce = null;
            }
            return ['annonce' => $annonce, 'errors' => [$e->getMessage()]];
        }

        if (!$isNew) {
            $annonce->update($title, $description, $price, $tags);
        }
        foreach ($removed as $photo) {
            $annonce->removePhoto($photo);
        }
        foreach ($stored as $filename) {
            $annonce->addPhoto($filename);
        }
        $this->entityManager->flush();
        foreach ($removed as $photo) {
            $this->photoStorage->delete($annonce->getId(), $photo->getFilename());
        }

        return ['annonce' => $annonce, 'errors' => []];
    }

    /**
     * Renouvellement propose : annonce en ligne (expiree ou non), proche de son
     * expiration, et renouvellements pas epuises.
     */
    public function canRenew(Annonce $annonce): bool
    {
        if ($annonce->isClosed() || $annonce->getRenewCount() >= $this->settings->getInt(AnnonceSettings::MAX_RENEW)) {
            return false;
        }
        $windowStart = (clone $annonce->getExpiresAt())
            ->modify('-' . $this->settings->getInt(AnnonceSettings::RENEW_WINDOW_DAYS) . ' days');
        return $windowStart <= new \DateTime();
    }

    /**
     * @return string|null erreur affichable, null si renouvelee
     */
    public function renew(Annonce $annonce): ?string
    {
        if (!$this->canRenew($annonce)) {
            return "Cette annonce ne peut pas être renouvelée.";
        }
        // Une annonce expiree ne compte plus dans le quota : la remettre en ligne doit le respecter
        if ($annonce->isExpired() && $this->hasReachedQuota($annonce->getUser())) {
            return 'Vous avez déjà le nombre maximum d’annonces en ligne.';
        }
        $annonce->renew($this->newExpiry());
        $this->entityManager->flush();
        return null;
    }

    public function withdraw(Annonce $annonce): void
    {
        if (!$annonce->isClosed()) {
            $annonce->close(Annonce::STATUS_WITHDRAWN);
            $this->entityManager->flush();
        }
    }

    /**
     * Purge automatique, au plus une fois par jour (pas de tache planifiee) :
     * appelee a l'affichage de la liste des annonces.
     */
    public function purgeIfDue(): void
    {
        $today = (new \DateTime())->format('Y-m-d');
        if ($this->settings->get(AnnonceSettings::LAST_PURGE) === $today) {
            return;
        }
        $this->settings->set(AnnonceSettings::LAST_PURGE, $today);
        $this->purge();
    }

    /**
     * Supprime definitivement les annonces expirees ou retirees depuis plus
     * de N jours, avec leurs photos.
     *
     * @return int nombre d'annonces supprimees
     */
    public function purge(): int
    {
        $before = (new \DateTime())->modify('-' . $this->settings->getInt(AnnonceSettings::PURGE_DAYS) . ' days');
        $annonces = $this->entityManager->getRepository(Annonce::class)->findPurgeable($before);
        foreach ($annonces as $annonce) {
            $this->photoStorage->deleteAll($annonce->getId());
            $this->entityManager->remove($annonce);
        }
        $this->entityManager->flush();
        return count($annonces);
    }

    private function newExpiry(): \DateTime
    {
        return (new \DateTime())->modify('+' . $this->settings->getInt(AnnonceSettings::DURATION_DAYS) . ' days');
    }
}
