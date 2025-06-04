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
     * @ORM\Column(name="game_id", type="integer")
     */
    protected $game_id;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $user_id;

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

    public function getGameId()
    {
        return $this->game_id;
    }

    public function setGameId($game_id)
    {
        $this->game_id = $game_id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
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
