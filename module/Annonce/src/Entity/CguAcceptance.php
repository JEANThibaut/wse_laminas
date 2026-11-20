<?php
namespace Annonce\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * Preuve d'acceptation d'une version des CGU : qui, laquelle, quand, d'ou.
 *
 * @ORM\Entity
 * @ORM\Table(name="annonce_cgu_acceptance", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniq_annonce_cgu_acceptance", columns={"user_id", "cgu_version_id"})
 * })
 */
class CguAcceptance
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
     * @ORM\ManyToOne(targetEntity="Annonce\Entity\CguVersion")
     * @ORM\JoinColumn(name="cgu_version_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $cguVersion;

    /** @ORM\Column(name="accepted_at", type="datetime") */
    private $acceptedAt;

    /** @ORM\Column(type="string", length=45) */
    private $ip;

    public function __construct(User $user, CguVersion $cguVersion, string $ip)
    {
        $this->user = $user;
        $this->cguVersion = $cguVersion;
        $this->ip = mb_substr($ip, 0, 45);
        $this->acceptedAt = new \DateTime();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCguVersion(): CguVersion
    {
        return $this->cguVersion;
    }

    public function getAcceptedAt(): \DateTime
    {
        return $this->acceptedAt;
    }

    public function getIp(): string
    {
        return (string) $this->ip;
    }
}
