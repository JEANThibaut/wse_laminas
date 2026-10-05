<?php
namespace User\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Abonnement aux notifications d'un appareil (navigateur ou application
 * installee). Un compte en a un par appareil.
 *
 * @ORM\Entity
 * @ORM\Table(name="push_subscription", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniq_push_subscription_endpoint", columns={"endpoint_hash"})
 * }, indexes={
 *     @ORM\Index(name="idx_push_subscription_user", columns={"user_id"})
 * })
 */
class PushSubscription
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * Adresse fournie par le service de push du navigateur (Google, Apple, Mozilla).
     *
     * @ORM\Column(type="text")
     */
    private $endpoint;

    /**
     * SHA-256 de l'endpoint : sert a l'unicite, l'endpoint etant trop long pour un index.
     *
     * @ORM\Column(name="endpoint_hash", type="string", length=64, options={"fixed": true})
     */
    private $endpointHash;

    /**
     * Cles de chiffrement des messages, fournies par le navigateur.
     *
     * @ORM\Column(type="string", length=255)
     */
    private $p256dh;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $auth;

    /**
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=true)
     */
    private $userAgent;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct(User $user, string $endpoint, string $p256dh, string $auth, ?string $userAgent)
    {
        $this->endpoint = $endpoint;
        $this->endpointHash = self::hashEndpoint($endpoint);
        $this->createdAt = new \DateTime();
        $this->update($user, $p256dh, $auth, $userAgent);
    }

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Le navigateur renouvelle parfois ses cles, et un appareil peut changer de compte.
     */
    public function update(User $user, string $p256dh, string $auth, ?string $userAgent): void
    {
        $this->user = $user;
        $this->p256dh = $p256dh;
        $this->auth = $auth;
        $this->userAgent = $userAgent !== null ? mb_substr($userAgent, 0, 255) : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function getP256dh(): string
    {
        return $this->p256dh;
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
