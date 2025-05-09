<?php

declare(strict_types=1);

namespace Parties\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="integer")
     */
    private int $partie_id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $user_email;

    /**
     * @ORM\Column(type="integer")
     */
    private int $user_id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $presence;

    /**
     * @ORM\Column(type="integer")
     */
    private int $paid;

    // Getters and Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getPartieId(): int
    {
        return $this->partie_id;
    }

    public function setPartieId(int $partie_id): void
    {
        $this->partie_id = $partie_id;
    }

    public function getUserEmail(): string
    {
        return $this->user_email;
    }

    public function setUserEmail(string $user_email): void
    {
        $this->user_email = $user_email;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
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
