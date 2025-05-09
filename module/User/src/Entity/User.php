<?php

declare(strict_types=1);

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
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $nickname;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $birthday;

    /**
     * @ORM\Column(type="string", length=150, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $role;

    /**
     * @ORM\Column(type="integer")
     */
    private $blacklist ;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="integer")
     */
    private $gotKey ;

        /**
     * @ORM\Column(type="integer")
     */
    private $gotRemote;

        /**
     * @ORM\Column(type="integer")
     */
    private $isActive;


    // Getters and Setters
    public function getId()
    {
        return $this->id;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    public function getNickname()
    {
        return $this->nickname;
    }

    public function setNickname($nickname)
    {
        $this->nickname = $nickname;
    }

    public function getBirthday()
    {
        return $this->birthday;
    }

    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function isBlacklist()
    {
        return $this->blacklist;
    }

    public function setBlacklist($blacklist)
    {
        $this->blacklist = $blacklist;
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getGotKey()
    {
        return $this->gotKey;
    }

    public function setGotKey($gotKey)
    {
        $this->gotKey = $gotKey;
    }

    public function getGotRemote()
    {
        return $this->gotRemote;
    }

    public function setGotRemote($gotRemote)
    {
        $this->gotRemote = $gotRemote;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }
    public function hasRole(string $requiredRole, ?string $module = null): bool
    {
        // Convertir le champ role en tableau si ce n'est pas déjà un tableau
        $roles = is_array($this->role) ? $this->role : json_decode($this->role, true);
    
        // Vérifier si le rôle global "god" est présent
        if (in_array('god', $roles, true)) {
            return true;
        }
    
        if ($module && isset($roles[$module])) {
            return in_array($requiredRole, $roles[$module], true);
        }
    
        return in_array($requiredRole, $roles, true);
    }
    

    /**
     * Retourne tous les rôles d'un module ou globaux.
     * @param string|null $module Module spécifique (facultatif).
     * @return array
     */
    public function getRoles(?string $module = null): array
    {
        if ($this->role === 'god') {
            return ['god']; // Retourne uniquement le rôle global "God"
        }

        $roles = json_decode($this->role, true);

        if ($module && isset($roles[$module])) {
            return $roles[$module];
        }

        return [];
    }

}
