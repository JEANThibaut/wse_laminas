<?php

namespace Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Game\Repository\GameRepository")
 * @ORM\Table(name="waiting_list")
 */
class WaitingList
{
    /**
     * @ORM\Id
     * @ORM\Column(name="idwaiting", type="integer")
     * @ORM\GeneratedValue
     */
    protected $idwaiting;

    /**
     * @ORM\ManyToOne(targetEntity="Game\Entity\Game")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="idgame", nullable=false)
     */
    private $game;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false)
     */
    private $user;


    /**
     * @ORM\Column(name="is_validate", type="integer")
     */
    protected $is_validate;

    /**
     * @ORM\Column(name="email_send", type="integer")
     */
    protected $email_send;

    /**
     * @ORM\Column(name="order_list", type="integer")
     */
    protected $order_list;

    public function getIdwaiting()
    {
        return $this->idwaiting;
    }

    public function setIdwaiting($idwaiting)
    {
        $this->idwaiting = $idwaiting;
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


    public function getIsValidate()
    {
        return $this->is_validate;
    }

    public function setIsValidate($is_validate)
    {
        $this->is_validate = $is_validate;
    }

    public function getEmailSend()
    {
        return $this->email_send;
    }

    public function setEmailSend($email_send)
    {
        $this->email_send = $email_send;
    }

    public function getOrderList()
    {
        return $this->order_list;
    }

    public function setOrderList($order_list)
    {
        $this->order_list = $order_list;
    }
}
