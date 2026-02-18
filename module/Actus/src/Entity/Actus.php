<?php
namespace Actus\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Actus\Repository\ActusRepository")
 * @ORM\Table(name="actus")
 */
class Actus
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $idactus;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $type;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $titre;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $contenu;

    /** @ORM\Column(type="string", length=255, nullable=true) */
    private $lien;

    public function getIdactus()
    {
        return $this->idactus;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($titre)
    {
        $this->titre = $titre;
        return $this;
    }

    public function getContenu()
    {
        return $this->contenu;
    }

    public function setContenu($contenu)
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getLien()
    {
        return $this->lien;
    }

    public function setLien($lien)
    {
        $this->lien = $lien;
        return $this;
    }
}
