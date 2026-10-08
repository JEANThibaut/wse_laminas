<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Domaine d'email refuse (adresses jetables), gere par le GOD
 * (GOD MODE > Emails interdits). Refuse le domaine et ses sous-domaines ;
 * '*' sert de joker (yopmail.* : yopmail.com, yopmail.fr...).
 *
 * @ORM\Entity
 * @ORM\Table(name="blocked_email_domain", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniq_blocked_email_domain", columns={"domain"})
 * })
 */
class BlockedEmailDomain
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $domain;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Saisie libre ramenee a un domaine : minuscules, sans espace ni '@'
     * devant. '' si la saisie n'est pas un domaine valable.
     */
    public static function normalize(string $input): string
    {
        $domain = ltrim(strtolower(trim($input)), '@');
        if (!preg_match('/\A[a-z0-9*][a-z0-9*.\-]*\z/', $domain) || !str_contains($domain, '.') && !str_contains($domain, '*')) {
            return '';
        }
        return $domain;
    }
}
