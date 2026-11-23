<?php
namespace Organisation\Service;

use Application\Service\PushService;
use Doctrine\ORM\EntityManager;
use Organisation\Entity\Post;
use Organisation\Util\FrenchDate;
use User\Entity\User;

/**
 * Notification push d'une nouvelle publication aux comptes qui ont acces a
 * l'espace Organisation. PushService ecarte ensuite ceux qui n'ont pas acces
 * aux notifications (config features.notification), qui ont coupe la
 * categorie "Organisation" dans leur profil, ou qui n'ont aucun appareil abonne.
 */
class OrganisationNotifier
{
    private EntityManager $entityManager;
    private OrganisationAccess $access;
    private PushService $pushService;

    public function __construct(EntityManager $entityManager, OrganisationAccess $access, PushService $pushService)
    {
        $this->entityManager = $entityManager;
        $this->access = $access;
        $this->pushService = $pushService;
    }

    /**
     * @return array{recipients: int, sent: int} comptes vises et appareils atteints
     */
    public function notifyNew(Post $post, User $author, string $url): array
    {
        $recipients = array_values(array_filter(
            $this->candidates(),
            fn (User $user) => $user->getIdUser() !== $author->getIdUser() && $this->access->canAccess($user)
        ));
        if (!$recipients || !$this->pushService->isConfigured()) {
            return ['recipients' => 0, 'sent' => 0];
        }

        $body = $post->getTitle();
        if ($post->isEvent() && $post->getStartsAt()) {
            $body .= ' — ' . FrenchDate::dayTime($post->getStartsAt());
        }
        $report = $this->pushService->send(
            $recipients,
            PushService::CATEGORY_ORGANISATION,
            'Organisation : ' . mb_strtolower($post->getTypeLabel()),
            $body,
            $url
        );
        return ['recipients' => $report['recipients'], 'sent' => $report['sent']];
    }

    /**
     * Comptes susceptibles d'avoir acces : membres de l'association et admins
     * (le filtre exact est fait par OrganisationAccess).
     *
     * @return User[]
     */
    private function candidates(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.member = 1 OR u.admin = 1 OR u.roles LIKE :admin OR u.roles LIKE :god')
            ->setParameter('admin', '%admin%')
            ->setParameter('god', '%god%')
            ->getQuery()
            ->getResult();
    }
}
