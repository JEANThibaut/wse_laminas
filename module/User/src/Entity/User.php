<?php
namespace User\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="User\Repository\UserRepository")
 * @ORM\Table(name="user")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $iduser;

    /** @ORM\Column(type="string") */
    private $email;

    /** @ORM\Column(type="text", nullable=true) */
    private $roles;

    /** @ORM\Column(type="string") */
    private $password;

    /** @ORM\Column(type="string") */
    private $firstname;

    /** @ORM\Column(type="string") */
    private $lastname;

    /** @ORM\Column(type="boolean") */
    private $member;

    /** @ORM\Column(type="boolean") */
    private $admin;

    /** @ORM\Column(type="boolean") */
    private $blacklist;

    /** @ORM\Column(type="boolean") */
    private $isActive;

    /** @ORM\Column(type="datetime", nullable=true) */
    private $birthdate;

    /** @ORM\Column(type="string", nullable=true) */
    private $nickname;

    /** @ORM\Column(type="string", nullable=true) */
    private $resetToken;

    public function getIdUser(): ?int
    {
        return $this->iduser;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRoles(): ?string
    {
        return $this->roles;
    }

    public function setRoles(?string $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }



    public function getIsMember(): bool
    {
        return $this->member;
    }

    public function setIsMember(bool $member): self
    {
        $this->member = $member;
        return $this;
    }


    public function getIsAdmin(): bool
    {
        return $this->admin;
    }

    public function setIsAdmin(bool $admin): self
    {
        $this->admin = $admin;
        return $this;
    }

    public function getIsBlacklist(): bool
    {
        return $this->blacklist;
    }

    public function setIsBlacklist(bool $blacklist): self
    {
        $this->blacklist = $blacklist;
        return $this;
    }


    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }


    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;
        return $this;
    }


    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function isInRoles($role): bool
    {
        $roles = $this->getRoles();
    
        if (is_string($roles)) {
            $roles = json_decode($roles, true);
        }
    
        return is_array($roles) && in_array($role, $roles, true);
    }
    
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    // public function hasAnyRole(array $rolesToCheck): bool
    // {
    //     $roles = $this->getRoles();
    
    //     if (is_string($roles)) {
    //         $roles = json_decode($roles, true);
    //     }
    
    //     if (!is_array($roles)) return false;
    
    //     return (bool) array_intersect($roles, $rolesToCheck);
    // }

}
