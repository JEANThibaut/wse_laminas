<?php

declare(strict_types=1);

namespace Parties\Entity;

use Doctrine\ORM\Mapping as ORM;
use Users\Entity\Users;

/**
  * @ORM\Entity(repositoryClass="Parties\Repository\PartiesRepository")
 * @ORM\Table(name="participants")
 */
class Participants
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer") // ID de la partie
     */
    private int $partie;

    /**
     * @ORM\Column(type="integer") // ID de l'utilisateur
     */
    private int $user;

    /**
     * @ORM\Column(type="integer")
     */
    private int $presence;

    /**
     * @ORM\Column(type="integer")
     */
    private int $paid;

    // Getters et Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getPartie(): int
    {
        return $this->partie;
    }

    public function setPartie(int $partie): void
    {
        $this->partie = $partie;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getPresence(): int
    {
        return $this->presence;
    }

    public function setPresence(int $presence): void
    {
        $this->presence = $presence;
    }

    public function getPaid(): int
    {
        return $this->paid;
    }

    public function setPaid(int $paid): void
    {
        $this->paid = $paid;
    }
}

