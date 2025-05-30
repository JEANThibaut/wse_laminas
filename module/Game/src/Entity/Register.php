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

    /** @ORM\Column(type="integer") */
    private $user_id;

    /** @ORM\Column(type="integer") */
    private $game_id;

    /** @ORM\Column(type="integer") */
    private $presence;

    /** @ORM\Column(type="integer") */
    private $paid;
    /** @ORM\Column(type="integer") */
    private $member;

public function getIdregister()
{
    return $this->idregister;
}

public function getUserId()
{
    return $this->user_id;
}

public function setUserId($user_id)
{
    $this->user_id = $user_id;
    return $this;
}

public function getGameId()
{
    return $this->game_id;
}

public function setGameId($game_id)
{
    $this->game_id = $game_id;
    return $this;
}

public function getPresence()
{
    return $this->presence;
}

public function setPresence($presence)
{
    $this->presence = $presence;
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
