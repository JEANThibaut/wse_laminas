<?php
namespace Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="game")
 */
class Game
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $idgame;

    /** @ORM\Column(type="datetime") */
    private $date;

    /** @ORM\Column(type="string") */
    private $player_max;

    /** @ORM\Column(type="integer") */
    private $status;

    public function getIdgame()
    {
        return $this->idgame;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getPlayerMax()
    {
        return $this->player_max;
    }

    public function setPlayerMax($player_max): self
    {
        $this->player_max = $player_max;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }
    
    public function setStatus( $status): self
    {
        $this->status = $status;
        return $this;
    }
  
}
