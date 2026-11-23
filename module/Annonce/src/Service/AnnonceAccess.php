<?php
namespace Annonce\Service;

use Application\Service\FeatureAccess;
use Doctrine\ORM\EntityManager;
use Game\Entity\GameRegister;
use Moderation\Service\SanctionService;
use User\Entity\User;

/**
 * Qui peut voir l'espace Petites annonces et qui peut y publier.
 *
 * Voir : fonctionnalite "annonces" ouverte a son niveau (config features :
 * GOD seulement tant que l'espace est en test), compte valide (email
 * confirme), ni bloque ni desactive.
 * Publier : en plus, ne pas etre interdit de publier par la moderation, et
 * avoir ete pointe sur une partie passee, ou avoir valide son email depuis
 * au moins N jours (GOD MODE > Petites annonces).
 * Le GOD passe toujours, pour pouvoir tout tester.
 */
class AnnonceAccess
{
    public const FEATURE = 'annonces';

    private EntityManager $entityManager;
    private FeatureAccess $featureAccess;
    private AnnonceSettings $settings;
    private SanctionService $sanctions;

    public function __construct(EntityManager $entityManager, FeatureAccess $featureAccess, AnnonceSettings $settings, SanctionService $sanctions)
    {
        $this->entityManager = $entityManager;
        $this->featureAccess = $featureAccess;
        $this->settings = $settings;
        $this->sanctions = $sanctions;
    }

    public function canAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->isGod()) {
            return true;
        }
        if ($user->isBlocked() || $user->isDeactivated()) {
            return false;
        }
        // Moderateur (nomme par le GOD) : voit l'espace meme ferme aux autres, pour examiner les annonces
        if ($user->isModerator()) {
            return true;
        }
        return $this->featureAccess->isAllowed(self::FEATURE, $user) && $user->isMailValidated();
    }

    /**
     * Raison pour laquelle le membre ne peut pas encore publier, null s'il le peut.
     */
    public function publishRestriction(User $user): ?string
    {
        if ($user->isGod()) {
            return null;
        }
        // Interdiction de publier prononcee par la moderation
        if ($restriction = $this->sanctions->publishRestriction($user)) {
            return $restriction;
        }
        if ($this->hasPlayed($user)) {
            return null;
        }
        $validatedAt = $user->getDateValidation();
        if ($validatedAt === null) {
            return 'Vous pourrez publier des annonces après votre première partie.';
        }
        $allowedFrom = (new \DateTime($validatedAt->format('Y-m-d H:i:s')))
            ->modify('+' . $this->settings->getInt(AnnonceSettings::MIN_DAYS) . ' days');
        if ($allowedFrom <= new \DateTime()) {
            return null;
        }
        return 'Vous pourrez publier des annonces après votre première partie, ou à partir du '
            . $allowedFrom->format('d/m/Y') . '.';
    }

    /**
     * A ete pointe (arrive et paye) sur au moins une partie deja passee.
     */
    private function hasPlayed(User $user): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.idregister)')
            ->from(GameRegister::class, 'r')
            ->innerJoin('r.game', 'g')
            ->where('r.user = :user')
            ->andWhere('r.status = :validated')
            ->andWhere('g.date < :now')
            ->setParameter('user', $user)
            ->setParameter('validated', GameRegister::STATUS_VALIDATED)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
