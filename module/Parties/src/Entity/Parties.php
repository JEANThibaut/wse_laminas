<?php

declare(strict_types=1);

namespace Parties\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Parties\Entity\Participants;


/**
 * @ORM\Entity(repositoryClass="Parties\Repository\PartiesRepository")
 * @ORM\Table(name="parties")
 */
class Parties
{

    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="datetime", length=100)
     */
    private DateTime $date;

    /**
     * @ORM\Column(type="integer", length=100)
     */
    private int $joueursMax;

    /**
     * @ORM\Column(type="boolean", length=100)
     */
    private $isActive;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private string $photosUrl;

  
    // Getters and Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getJoueursMax(): int
    {
        return $this->joueursMax;
    }

    public function setJoueursMax(int $joueursMax): void
    {
        $this->joueursMax = $joueursMax;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getPhotosUrl(): string
    {
        return $this->photosUrl;
    }

    public function setPhotosUrl(string $photosUrl): void
    {
        $this->photosUrl = $photosUrl;
    }

}
