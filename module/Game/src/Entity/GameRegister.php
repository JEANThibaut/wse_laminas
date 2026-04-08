<?php
namespace Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Game\Repository\GameRepository")
 * @ORM\Table(name="game_register")
 */
class GameRegister
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $idregister;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Game\Entity\Game")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="idgame", nullable=false)
     */
    private $game;

    /** @ORM\Column(type="integer") */
    private $paid;
    /** @ORM\Column(type="integer") */
    private $member;
    /** @ORM\Column(type="integer") */
    private $arrived_number;
    /** @ORM\Column(type="string", length=32) */
    private $status = self::STATUS_ACTIVE;

public function getIdregister()
{
    return $this->idregister;
}

public function getUser()
{
    return $this->user;
}

public function setUser($user)
{
    $this->user = $user;
    return $this;
}

public function getGame()
{
    return $this->game;
}

public function setGame($game)
{
    $this->game = $game;
    return $this;
}

public function getPaid()
{
    return $this->paid;
}

public function setPaid($paid)
{
    $this->paid = $paid;
    return $this;
}

public function getMember()
{
    return $this->member;
}

public function setMember($member)
{
    $this->member = $member;
    return $this;
}

public function getArrivedNumber()
{
    return $this->arrived_number;
}

public function setArrivedNumber($arrived_number)
{
    $this->arrived_number = $arrived_number;
    return $this;
}

public function getStatus()
{
    return $this->status;
}

public function setStatus($status)
{
    $this->status = $status;
    return $this;
}

public function isActive()
{
    return $this->status === self::STATUS_ACTIVE;
}

  
}
