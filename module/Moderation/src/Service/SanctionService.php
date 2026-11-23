<?php
namespace Moderation\Service;

use Doctrine\ORM\EntityManager;
use Moderation\Entity\Sanction;
use User\Entity\User;

/**
 * Sanctions des membres et leur effet sur la publication d'annonces.
 * Echelle : avertissement, puis interdiction temporaire, puis definitive ;
 * le blocage du compte (blacklist) est reserve aux fautes graves.
 */
class SanctionService
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Interdiction de publier en vigueur, sous forme de message, ou null.
     */
    public function publishRestriction(User $user): ?string
    {
        foreach ($this->history($user) as $sanction) {
            if (!$sanction->isActive()) {
                continue;
            }
            if ($sanction->getType() === Sanction::TYPE_BAN) {
                return 'Vous ne pouvez plus publier d’annonces (décision de la modération).';
            }
            if ($sanction->getType() === Sanction::TYPE_SUSPENSION) {
                return 'Vous ne pouvez plus publier d’annonces jusqu’au ' . $sanction->getEndsAt()->format('d/m/Y') . ' (décision de la modération).';
            }
        }
        return null;
    }

    /**
     * @return Sanction[] sanctions du membre, la plus recente d'abord
     */
    public function history(User $user): array
    {
        return $this->entityManager->getRepository(Sanction::class)->findBy(['user' => $user], ['createdAt' => 'DESC', 'id' => 'DESC']);
    }

    /**
     * @return Sanction[]
     */
    public function recent(int $limit = 50): array
    {
        return $this->entityManager->getRepository(Sanction::class)->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC'], $limit);
    }

    /**
     * Sanction suivante sur l'echelle, d'apres les sanctions non levees du membre.
     */
    public function suggestedType(User $user): string
    {
        $count = 0;
        foreach ($this->history($user) as $sanction) {
            if (!$sanction->isRevoked()) {
                $count++;
            }
        }
        return [Sanction::TYPE_WARNING, Sanction::TYPE_SUSPENSION][$count] ?? Sanction::TYPE_BAN;
    }

    public function apply(User $user, string $type, string $reason, int $days, ?User $admin): ?Sanction
    {
        if (!isset(Sanction::TYPES[$type]) || $user->isGod()) {
            return null;
        }
        $endsAt = $type === Sanction::TYPE_SUSPENSION ? (new \DateTime())->modify('+' . max(1, $days) . ' days') : null;
        $sanction = new Sanction($user, $type, $reason, $endsAt, $admin);
        $this->entityManager->persist($sanction);
        if ($type === Sanction::TYPE_BLACKLIST) {
            // Meme blocage que la fiche du joueur : connexion refusee, session fermee
            $user->setIsBlacklist(true);
        }
        $this->entityManager->flush();
        return $sanction;
    }

    public function revoke(Sanction $sanction): void
    {
        if ($sanction->isRevoked()) {
            return;
        }
        $sanction->revoke();
        if ($sanction->getType() === Sanction::TYPE_BLACKLIST) {
            $sanction->getUser()->setIsBlacklist(false);
        }
        $this->entityManager->flush();
    }
}
