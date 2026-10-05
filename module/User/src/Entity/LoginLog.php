<?php
namespace User\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Evenement de connexion d'un compte. Conserve LoginLogRepository::RETENTION.
 *
 * @ORM\Entity(repositoryClass="User\Repository\LoginLogRepository")
 * @ORM\Table(name="login_log", indexes={
 *     @ORM\Index(name="idx_login_log_created_at", columns={"created_at"}),
 *     @ORM\Index(name="idx_login_log_user", columns={"user_id"}),
 *     @ORM\Index(name="idx_login_log_state", columns={"state"})
 * })
 */
class LoginLog
{
    // Connexion reussie (email + mot de passe)
    public const STATE_SUCCESS = 'success';
    // Connexion automatique apres creation du compte
    public const STATE_SIGNUP = 'signup';
    // Compte existant, mauvais mot de passe
    public const STATE_WRONG_PASSWORD = 'wrong_password';
    // Email inconnu : aucun compte lie
    public const STATE_UNKNOWN_EMAIL = 'unknown_email';
    // Deconnexion volontaire
    public const STATE_LOGOUT = 'logout';

    public const STATE_LABELS = [
        self::STATE_SUCCESS => 'Connexion',
        self::STATE_SIGNUP => 'Inscription',
        self::STATE_WRONG_PASSWORD => 'Mauvais mot de passe',
        self::STATE_UNKNOWN_EMAIL => 'Email inconnu',
        self::STATE_LOGOUT => 'Déconnexion',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Compte concerne. Vide si l'email ne correspond a aucun compte.
     *
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=true, onDelete="SET NULL")
     */
    private $user;

    /**
     * Email saisi, tel quel (celui du compte pour une deconnexion).
     *
     * @ORM\Column(type="string", length=180)
     */
    private $email;

    /**
     * Un des self::STATE_*.
     *
     * @ORM\Column(type="string", length=32)
     */
    private $state;

    /**
     * IP du client, resolue derriere le proxy de l'hebergeur (Application\Util\ClientIp).
     *
     * @ORM\Column(type="string", length=45)
     */
    private $ip;

    /**
     * @ORM\Column(name="user_agent", type="string", length=255, nullable=true)
     */
    private $userAgent;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct(string $state, string $email, ?User $user, string $ip, ?string $userAgent)
    {
        $this->state = $state;
        $this->email = mb_substr($email, 0, 180);
        $this->user = $user;
        $this->ip = mb_substr($ip, 0, 45);
        $this->userAgent = $userAgent !== null ? mb_substr($userAgent, 0, 255) : null;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getStateLabel(): string
    {
        return self::STATE_LABELS[$this->state] ?? $this->state;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Appareil et navigateur en clair ("iPhone · Safari"), deduits du user-agent.
     * Approximatif : sert a reperer d'un coup d'oeil, pas a identifier.
     */
    public function getDeviceLabel(): string
    {
        $ua = (string) $this->userAgent;
        if ($ua === '') {
            return 'Appareil inconnu';
        }

        $devices = [
            'iPhone' => 'iPhone', 'iPad' => 'iPad', 'Android' => 'Android',
            'Windows' => 'Windows', 'Macintosh' => 'Mac', 'Linux' => 'Linux',
        ];
        // Ordre important : Edge et Opera se declarent aussi Chrome, Chrome se declare Safari
        $browsers = [
            'Edg/' => 'Edge', 'OPR/' => 'Opera', 'SamsungBrowser' => 'Samsung',
            'Firefox' => 'Firefox', 'FxiOS' => 'Firefox', 'CriOS' => 'Chrome',
            'Chrome' => 'Chrome', 'Safari' => 'Safari',
        ];

        $parts = [];
        foreach ([$devices, $browsers] as $patterns) {
            foreach ($patterns as $needle => $label) {
                if (stripos($ua, $needle) !== false) {
                    $parts[] = $label;
                    break;
                }
            }
        }
        return $parts ? implode(' · ', $parts) : 'Autre';
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
