<?php
namespace Game\Service;

use Application\Service\AuthService;
use Application\Service\PushService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use Game\Entity\QueueEntry;
use User\Entity\User;

/**
 * File d'attente des parties completes, en premier arrive, premier servi.
 *
 * Quand une place se libere (desinscription, place refusee ou expiree,
 * maximum augmente), elle est proposee au premier en attente : elle lui est
 * reservee pendant un delai (OFFER_HOURS, raccourci a SHORT_OFFER_HOURS la
 * veille et le jour de la partie, jamais au-dela du debut). Sans reponse, elle
 * passe au suivant. Le joueur est prevenu sur le site, par email et par
 * notification "Parties" s'il l'a activee.
 *
 * En phase de test (config queue.restricted), seuls les admins (GOD compris)
 * voient et rejoignent la file.
 *
 * Propositions automatiques (config queue.auto_offer) : sans elles (mode
 * manuel), une place liberee reste libre jusqu'a ce qu'un admin la propose a
 * un joueur de la file (offerTo) ; une proposition expiree n'est pas
 * reproposee.
 */
class QueueManager
{
    public const RESULT_JOINED = 'joined';
    public const RESULT_ALREADY_QUEUED = 'already_queued';
    public const RESULT_ALREADY_REGISTERED = 'already_registered';
    public const RESULT_PLACES_LEFT = 'places_left';
    public const RESULT_CLOSED = 'closed';
    public const RESULT_ACCEPTED = 'accepted';
    public const RESULT_NO_OFFER = 'no_offer';
    public const RESULT_DONE = 'done';

    private const OFFER_HOURS = 12;
    private const SHORT_OFFER_HOURS = 2;

    private EntityManager $entityManager;
    private GameManager $gameManager;
    private PushService $pushService;
    private AuthService $authService;
    private bool $restricted;
    private bool $autoOffer;
    private string $siteUrl;

    public function __construct(
        EntityManager $entityManager,
        GameManager $gameManager,
        PushService $pushService,
        AuthService $authService,
        bool $restricted,
        bool $autoOffer,
        string $siteUrl
    ) {
        $this->entityManager = $entityManager;
        $this->gameManager = $gameManager;
        $this->pushService = $pushService;
        $this->authService = $authService;
        $this->restricted = $restricted;
        $this->autoOffer = $autoOffer;
        $this->siteUrl = rtrim($siteUrl, '/');
    }

    /**
     * Le joueur peut-il voir et rejoindre la file ? (admins seulement en phase de test)
     */
    public function isAvailableFor(?User $user): bool
    {
        return $user !== null && (!$this->restricted || $user->hasAdminAccess());
    }

    /**
     * Etat de la file pour l'accueil d'un joueur.
     *
     * @return array{entry: ?QueueEntry, position: ?int, count: int}
     */
    public function getStatus(Game $game, User $user): array
    {
        $repository = $this->repository();
        $entry = $repository->findOpenEntry($game, $user);

        return [
            'entry' => $entry,
            'position' => $entry && $entry->isWaiting() ? $repository->getWaitingPosition($entry) : null,
            'count' => count($repository->findOpenForGame($game)),
        ];
    }

    /**
     * @return QueueEntry[] la file de la partie, dans l'ordre d'arrivee
     */
    public function getQueue(Game $game): array
    {
        return $this->repository()->findOpenForGame($game);
    }

    /**
     * Le joueur rejoint la file d'une partie complete.
     *
     * @return string self::RESULT_*
     */
    public function join(Game $game, User $user): string
    {
        if (!$this->isOpenForQueue($game)) {
            return self::RESULT_CLOSED;
        }

        return $this->entityManager->wrapInTransaction(function () use ($game, $user) {
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);

            if ($this->entityManager->getRepository(GameRegister::class)->findCurrentRegister($game, $user)) {
                return self::RESULT_ALREADY_REGISTERED;
            }
            if ($this->repository()->findOpenEntry($game, $user)) {
                return self::RESULT_ALREADY_QUEUED;
            }
            // Places libres et personne devant : inutile d'attendre
            if (!$this->gameManager->isFull($game) && !$this->repository()->findFirstWaiting($game)) {
                return self::RESULT_PLACES_LEFT;
            }
            $this->entityManager->persist(new QueueEntry($game, $user));
            $this->entityManager->flush();

            return self::RESULT_JOINED;
        });
    }

    /**
     * Le joueur quitte la file, place proposee comprise (elle passe au suivant).
     */
    public function leave(Game $game, User $user): string
    {
        $entry = $this->repository()->findOpenEntry($game, $user);
        if (!$entry) {
            return self::RESULT_NO_OFFER;
        }
        $this->closeAndRefill($entry, QueueEntry::STATUS_LEFT);

        return self::RESULT_DONE;
    }

    /**
     * Le joueur accepte la place qui lui est proposee : il est inscrit.
     */
    public function accept(Game $game, User $user): string
    {
        $result = $this->entityManager->wrapInTransaction(function () use ($game, $user) {
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);
            $entry = $this->repository()->findOpenEntry($game, $user);
            if (!$entry || !$entry->isOffered() || $entry->getOfferExpiresAt() <= new \DateTime()) {
                return self::RESULT_NO_OFFER;
            }
            if (!$this->entityManager->getRepository(GameRegister::class)->findCurrentRegister($game, $user)) {
                // La place lui etait reservee : pas de controle du maximum
                $this->gameManager->createRegister($game, $user);
            }
            $entry->close(QueueEntry::STATUS_ACCEPTED);
            $this->entityManager->flush();

            return self::RESULT_ACCEPTED;
        });
        if ($result === self::RESULT_NO_OFFER) {
            // Offre peut-etre expiree a l'instant : la faire passer au suivant
            $this->processExpiredOffers();
        }

        return $result;
    }

    /**
     * Le joueur refuse la place proposee : elle passe au suivant.
     */
    public function decline(Game $game, User $user): string
    {
        $entry = $this->repository()->findOpenEntry($game, $user);
        if (!$entry || !$entry->isOffered()) {
            return self::RESULT_NO_OFFER;
        }
        $this->closeAndRefill($entry, QueueEntry::STATUS_DECLINED);

        return self::RESULT_DONE;
    }

    /**
     * Un admin retire un joueur de la file.
     *
     * @return QueueEntry[] les places proposees au suivant, le cas echeant
     */
    public function removeByAdmin(QueueEntry $entry): array
    {
        if (!in_array($entry->getStatus(), QueueEntry::OPEN_STATUSES, true)) {
            return [];
        }
        return $this->closeAndRefill($entry, QueueEntry::STATUS_REMOVED);
    }

    /**
     * Un admin a inscrit directement le joueur : son entree de file est close.
     */
    public function onDirectRegistration(Game $game, User $user): void
    {
        $entry = $this->repository()->findOpenEntry($game, $user);
        if ($entry) {
            // Une place qui lui etait proposee est consommee par l'inscription : rien a reattribuer
            $entry->close(QueueEntry::STATUS_ACCEPTED);
            $this->entityManager->flush();
        }
    }

    public function isAutoOffer(): bool
    {
        return $this->autoOffer;
    }

    /**
     * Propose automatiquement les places libres aux premiers de la file. A
     * appeler des qu'une place peut s'etre liberee (desinscription, maximum
     * augmente...). Ne fait rien en mode manuel (config queue.auto_offer).
     *
     * @return QueueEntry[] les places proposees a l'instant
     */
    public function fillFreePlaces(Game $game): array
    {
        if (!$this->autoOffer || !$this->isOpenForQueue($game)) {
            return [];
        }
        $offered = $this->entityManager->wrapInTransaction(function () use ($game) {
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);
            $offered = [];
            $expiresAt = $this->computeOfferExpiry($game);
            while ($this->gameManager->countFreePlaces($game) > 0 && ($entry = $this->repository()->findFirstWaiting($game))) {
                $entry->offer($expiresAt);
                $this->entityManager->flush();
                $offered[] = $entry;
            }
            return $offered;
        });

        // Prevenir apres validation : un echec d'envoi ne doit pas annuler l'offre
        foreach ($offered as $entry) {
            $this->notifyOffer($entry);
        }
        return $offered;
    }

    public const RESULT_OFFERED = 'offered';
    public const RESULT_NO_PLACE = 'no_place';
    public const RESULT_NOT_WAITING = 'not_waiting';

    /**
     * Mode manuel : un admin propose une place libre a un joueur de la file,
     * avec le meme delai et les memes notifications que le mode automatique.
     *
     * @return string self::RESULT_OFFERED, RESULT_NO_PLACE, RESULT_NOT_WAITING ou RESULT_CLOSED
     */
    public function offerTo(QueueEntry $entry): string
    {
        $game = $entry->getGame();
        if (!$this->isOpenForQueue($game)) {
            return self::RESULT_CLOSED;
        }
        $result = $this->entityManager->wrapInTransaction(function () use ($entry, $game) {
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->refresh($entry);
            if (!$entry->isWaiting()) {
                return self::RESULT_NOT_WAITING;
            }
            if ($this->gameManager->countFreePlaces($game) <= 0) {
                return self::RESULT_NO_PLACE;
            }
            $entry->offer($this->computeOfferExpiry($game));
            $this->entityManager->flush();
            return self::RESULT_OFFERED;
        });
        if ($result === self::RESULT_OFFERED) {
            $this->notifyOffer($entry);
        }
        return $result;
    }

    /**
     * Places proposees sans reponse dans le delai : expirees, et proposees au
     * suivant en mode automatique. Appele a chaque requete (Game\Module) ; ne
     * fait rien s'il n'y a rien a expirer.
     */
    public function processExpiredOffers(): void
    {
        $games = [];
        foreach ($this->repository()->findExpiredOffers(new \DateTime()) as $entry) {
            $entry->close(QueueEntry::STATUS_EXPIRED);
            $games[$entry->getGame()->getIdGame()] = $entry->getGame();
        }
        if ($games) {
            $this->entityManager->flush();
            foreach ($games as $game) {
                $this->fillFreePlaces($game);
            }
        }
    }

    /**
     * Fin du delai pour accepter une place proposee maintenant : 12 h, 2 h la
     * veille et le jour de la partie, jamais apres son debut. Exprimee dans le
     * fuseau du serveur, comme les autres dates stockees.
     */
    public function computeOfferExpiry(Game $game, ?\DateTimeImmutable $now = null): \DateTimeImmutable
    {
        $paris = new \DateTimeZone(GameManager::TIMEZONE);
        $now = ($now ?? new \DateTimeImmutable('now'))->setTimezone($paris);
        $start = $this->gameManager->getGameStart($game);
        $dayBefore = $start->setTime(0, 0)->modify('-1 day');

        $hours = $now >= $dayBefore ? self::SHORT_OFFER_HOURS : self::OFFER_HOURS;
        $expiresAt = min($now->modify("+{$hours} hours"), $start);

        return $expiresAt->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    /**
     * @return QueueEntry[] les places proposees si l'entree avait une place reservee
     */
    private function closeAndRefill(QueueEntry $entry, string $status): array
    {
        $wasOffered = $entry->isOffered();
        $entry->close($status);
        $this->entityManager->flush();
        return $wasOffered ? $this->fillFreePlaces($entry->getGame()) : [];
    }

    /**
     * Partie ouverte (statut actif) et pas encore commencee.
     */
    private function isOpenForQueue(Game $game): bool
    {
        return (int) $game->getStatus() === 1
            && new \DateTimeImmutable('now', new \DateTimeZone(GameManager::TIMEZONE)) < $this->gameManager->getGameStart($game);
    }

    /**
     * Previent le joueur d'une place proposee : email, et notification
     * "Parties" s'il l'a activee. Jamais bloquant.
     */
    private function notifyOffer(QueueEntry $entry): void
    {
        $user = $entry->getUser();
        $gameDate = $entry->getGame()->getDate()->format('d/m/Y');
        $deadline = \DateTimeImmutable::createFromInterface($entry->getOfferExpiresAt())
            ->setTimezone(new \DateTimeZone(GameManager::TIMEZONE))
            ->format('d/m/Y à H\hi');

        try {
            $this->authService->sendNotificationMail(
                $user->getEmail(),
                "Une place se libère pour la partie du $gameDate",
                "Bonjour " . $user->getFirstname() . ",\n\n"
                . "Une place s'est libérée pour la partie du $gameDate et elle vous est réservée.\n"
                . "Confirmez-la avant le $deadline sur le site : " . $this->siteUrl . "/\n\n"
                . "Sans réponse d'ici là, elle sera proposée au joueur suivant de la file d'attente.\n\n"
                . "Wolf Soft Eure"
            );
        } catch (\Throwable $e) {
            error_log('file d\'attente, email : ' . $e->getMessage());
        }

        try {
            $this->pushService->send(
                [$user],
                PushService::CATEGORY_GAMES,
                'Une place se libère !',
                "Partie du $gameDate : une place vous est réservée jusqu'au $deadline.",
                '/'
            );
        } catch (\Throwable $e) {
            error_log('file d\'attente, notification : ' . $e->getMessage());
        }
    }

    private function repository(): \Game\Repository\QueueRepository
    {
        return $this->entityManager->getRepository(QueueEntry::class);
    }
}
