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


    /** @ORM\Column(type="text") */
    private $title;

    /** @ORM\Column(type="text") */
    private $description;

    
    /** @ORM\Column(type="text") */
    private $prix;

    /** @ORM\Column(type="integer") */
    private $validate;

    /** @ORM\Column(type="text") */
    private $photo_1;

    /** @ORM\Column(type="text") */
    private $photo_2;

        /** @ORM\Column(type="text") */
    private $photo_3;

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
  
     public function getDescription()
    {
        return $this->description;
    }
    
    public function setDescription( $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix()
    {
        return $this->prix;
    }
    
    public function setPrix( $prix): self
    {
        $this->prix = $prix;
        return $this;
    }
    
    public function getValidate()
    {
        return $this->validate;
    }
    
    public function setValidate( $validate): self
    {
        $this->validate = $validate;
        return $this;
    }
  

    public function getPhoto1()
    {
        return $this->photo1;
    }
    
    public function setPhoto1( $photo1): self
    {
        $this->photo1 = $photo1;
        return $this;
    }
    public function getPhoto2()
    {
        return $this->photo2;
    }
    
    public function setPhoto2( $photo2): self
    {
        $this->photo2 = $photo2;
        return $this;
    }
    public function getPhoto3()
    {
        return $this->photo3;
    }
    
    public function setPhoto3( $photo3): self
    {
        $this->photo3 = $photo3;
        return $this;
    }
}
