<?php
namespace Faction\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Faction\Repository\FactionRepository")
 * @ORM\Table(name="faction")
 */
class Faction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $idfaction;

    /**
     * @ORM\Column(name="faction_name")
     */
    private $faction_name;

    /**
     * @ORM\Column(name="description")
     */
    private $description;

    /**
     * @ORM\Column(name="bank_account")
     */
    private $bank_account;


        /**
     * @ORM\Column(name="icon")
     */
    private $icon;

    public function getIdFaction()
    {
        return $this->idfaction;
    }

    public function getFactionName()
    {
        return $this->faction_name;
    }

    public function setFactionName($faction_name)
    {
        $this->faction_name = $faction_name;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }
    
    public function getBankAccount()
    {
        return $this->bank_account;
    }

    public function setBankAccount( $bank_account)
    {
        $this->bank_account = $bank_account;
        return $this;
    }

        
    public function getIcon()
    {
        return $this->icon;
    }

    public function setIcon( $icon)
    {
        $this->icon = $icon;
        return $this;
    }


}
