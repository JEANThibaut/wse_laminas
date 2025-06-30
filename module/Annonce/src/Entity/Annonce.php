<?php
namespace Annonce\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Annonce\Repository\AnnonceRepository")
 * @ORM\Table(name="annonce")
 */
class Annonce
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $idannonce;

    /** @ORM\Column(type="datetime") */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="iduser", nullable=false)
     */
    private $user;


    /** @ORM\Column(type="integer") */
    private $title;

    public function getIdannonce()
    {
        return $this->idannonce;
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

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }
    
    public function setTitle( $title): self
    {
        $this->title = $title;
        return $this;
    }
  
}
