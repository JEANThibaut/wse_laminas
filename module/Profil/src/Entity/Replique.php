<?php
namespace Profil\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="replique")
 */
class Replique
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $idreplique;

    /** @ORM\Column(type="integer") */
    private $iduser;

    /** @ORM\Column(type="text", nullable=true) */
    private $nom_replique;

    /** @ORM\Column(type="string") */
    private $type_replique;

    /** @ORM\Column(type="string") */
    private $puissance;


    public function getIdreplique()
    {
        return $this->idreplique;
    }

    public function getNomReplique()
    {
        return $this->nom_replique;
    }

    public function setNomReplique(?string $nom_replique): self
    {
        $this->nom_replique = $nom_replique;
        return $this;
    }


    public function getTypeReplique()
    {
        return $this->type_replique;
    }

    public function setTypeReplique(?string $type_replique): self
    {
        $this->type_replique = $type_replique;
        return $this;
    }


    public function getPuissance()
    {
        return $this->puissance;
    }
    
    public function setPuissance(?string $puissance): self
    {
        $this->puissance = $puissance;
        return $this;
    }

    public function getIdUser(){
        return $this->iduser;
    }

    public function setIdUser( $iduser)
    {
        $this->iduser = $iduser;
        return $this;
    }

  
}
