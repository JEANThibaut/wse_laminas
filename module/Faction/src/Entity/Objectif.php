<?php
namespace Faction\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Faction\Repository\FactionRepository")
 * @ORM\Table(name="faction_objectif")
 */
class Objectif
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $idobjectif;

    /**
     * @ORM\Column(name="idfaction")
     */
    private $idfaction;

    /**
     * @ORM\Column(name="description")
     */
    private $description;

    /**
     * @ORM\Column(name="label")
     */
    private $label;

    /**
     * @ORM\Column(name="echelle")
     */
    private $echelle;

    /**
     * @ORM\Column(name="avance")
     */
    private $avance;

    /**
     * @ORM\Column(name="unit")
     */
    private $unit;

    public function getIdobjectif()
    {
        return $this->idobjectif;
    }

    public function setIdobjectif($idobjectif)
    {
        $this->idobjectif = $idobjectif;
        return $this;
    }

    public function getIdfaction()
    {
        return $this->idfaction;
    }

    public function setIdfaction($idfaction)
    {
        $this->idfaction = $idfaction;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    public function getEchelle()
    {
        return $this->echelle;
    }

    public function setEchelle($echelle)
    {
        $this->echelle = $echelle;
        return $this;
    }

    public function getAvance()
    {
        return $this->avance;
    }

    public function setAvance($avance)
    {
        $this->avance = $avance;
        return $this;
    }

    public function getUnit()
    {
        return $this->unit;
    }
    public function setUnit($unit)
    {
        $this->unit = $unit;
        return $this;
    }

}
