<?php
namespace Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Game\Repository\GameRepository")
 * @ORM\Table(name="register")
 */
class Register
{
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

  
}
