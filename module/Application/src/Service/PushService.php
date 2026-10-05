<?php
namespace Application\Service;

use Doctrine\ORM\EntityManager;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use User\Entity\PushSubscription;
use User\Entity\User;

/**
 * Notifications Web Push : abonnements des appareils et envoi.
 * Tout passe par PwaAccessPolicy : un compte non autorise ne peut ni
 * s'abonner ni recevoir, quel que soit le destinataire demande.
 */
class PushService
{
    // Limites de taille : un message push chiffre ne depasse pas ~4 Ko
    public const TITLE_MAX = 60;
    public const BODY_MAX = 200;

    private EntityManager $entityManager;
    private PwaAccessPolicy $policy;
    /** @var array{subject?: string, public_key?: string, private_key?: string} */
    private array $vapid;

    public function __construct(EntityManager $entityManager, PwaAccessPolicy $policy, array $vapid)
    {
        $this->entityManager = $entityManager;
        $this->policy = $policy;
        $this->vapid = $vapid;
    }

    /**
     * Les cles VAPID sont-elles configurees ? Sans elles, rien n'est propose.
     */
    public function isConfigured(): bool
    {
        return !empty($this->vapid['public_key']) && !empty($this->vapid['private_key']);
    }

    public function getPublicKey(): string
    {
        return (string) ($this->vapid['public_key'] ?? '');
    }

    public function canUse(?User $user): bool
    {
        return $this->isConfigured() && $this->policy->isAllowed($user);
    }

    /**
     * Enregistre l'abonnement d'un appareil, ou le met a jour s'il existe deja
     * (cles renouvelees, ou appareil passe a un autre compte).
     */
    public function subscribe(User $user, string $endpoint, string $p256dh, string $auth, ?string $userAgent): void
    {
        $subscription = $this->findByEndpoint($endpoint);
        if ($subscription) {
            $subscription->update($user, $p256dh, $auth, $userAgent);
        } else {
            $this->entityManager->persist(new PushSubscription($user, $endpoint, $p256dh, $auth, $userAgent));
        }
        $this->entityManager->flush();
    }

    public function unsubscribe(User $user, string $endpoint): void
    {
        $subscription = $this->findByEndpoint($endpoint);
        if ($subscription && $subscription->getUser()->getIdUser() === $user->getIdUser()) {
            $this->entityManager->remove($subscription);
            $this->entityManager->flush();
        }
    }

    /**
     * Comptes autorises qui ont au moins un appareil abonne, parmi ceux donnes.
     *
     * @param User[] $users
     * @return array<int, PushSubscription[]> abonnements par iduser
     */
    public function findSubscriptions(array $users): array
    {
        $allowed = array_filter($users, fn ($user) => $this->policy->isAllowed($user));
        if (!$allowed) {
            return [];
        }
        $subscriptions = $this->entityManager->getRepository(PushSubscription::class)->findBy([
            'user' => array_map(fn (User $user) => $user->getIdUser(), array_values($allowed)),
        ]);

        $byUser = [];
        foreach ($subscriptions as $subscription) {
            $byUser[$subscription->getUser()->getIdUser()][] = $subscription;
        }
        return $byUser;
    }

    /**
     * Envoie une notification a tous les appareils des comptes donnes.
     * Les comptes non autorises sont ecartes, les abonnements expires supprimes.
     *
     * @param User[] $users
     * @return array{requested: int, skipped: int, recipients: int, devices: int, sent: int, failed: int, removed: int, errors: string[]}
     */
    public function send(array $users, string $title, string $body, string $url = '/'): array
    {
        // Un meme compte peut arriver plusieurs fois (inscrit et en file, par exemple)
        $unique = [];
        foreach ($users as $user) {
            $unique[$user->getIdUser()] = $user;
        }
        $users = array_values($unique);
        $byUser = $this->findSubscriptions($users);
        $report = [
            'requested' => count($users),
            'skipped' => count(array_filter($users, fn ($user) => !$this->policy->isAllowed($user))),
            'recipients' => count($byUser),
            'devices' => 0,
            'sent' => 0,
            'failed' => 0,
            'removed' => 0,
            'errors' => [],
        ];
        if (!$byUser || !$this->isConfigured()) {
            return $report;
        }

        $payload = json_encode([
            'title' => mb_substr($title, 0, self::TITLE_MAX),
            'body' => mb_substr($body, 0, self::BODY_MAX),
            // Lien interne uniquement
            'url' => str_starts_with($url, '/') && !str_starts_with($url, '//') ? $url : '/',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $webPush = new WebPush(['VAPID' => [
            'subject' => $this->vapid['subject'] ?? 'mailto:contact@wolfsofteure.fr',
            'publicKey' => $this->vapid['public_key'],
            'privateKey' => $this->vapid['private_key'],
        ]], ['TTL' => 86400, 'urgency' => 'normal']);

        $byEndpoint = [];
        foreach ($byUser as $subscriptions) {
            foreach ($subscriptions as $subscription) {
                $byEndpoint[$subscription->getEndpoint()] = $subscription;
                $webPush->queueNotification(
                    new Subscription($subscription->getEndpoint(), $subscription->getP256dh(), $subscription->getAuth(), 'aes128gcm'),
                    $payload
                );
                $report['devices']++;
            }
        }

        foreach ($webPush->flush() as $result) {
            if ($result->isSuccess()) {
                $report['sent']++;
                continue;
            }
            $report['failed']++;
            // Appareil desabonne ou application desinstallee : on oublie l'abonnement
            if ($result->isSubscriptionExpired() && isset($byEndpoint[$result->getEndpoint()])) {
                $this->entityManager->remove($byEndpoint[$result->getEndpoint()]);
                $report['removed']++;
            } else {
                $report['errors'][] = $result->getReason();
            }
        }
        $this->entityManager->flush();

        return $report;
    }

    private function findByEndpoint(string $endpoint): ?PushSubscription
    {
        return $this->entityManager->getRepository(PushSubscription::class)->findOneBy([
            'endpointHash' => PushSubscription::hashEndpoint($endpoint),
        ]);
    }
}
