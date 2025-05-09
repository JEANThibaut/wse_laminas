<?php

declare(strict_types=1);

namespace Parties\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
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
     * @ORM\Column(type="string", length=100)
     */
    private string $date;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private string $joueursMax;

    /**
     * @ORM\Column(type="string", length=100)
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

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function getJoueursMax(): string
    {
        return $this->joueursMax;
    }

    public function setJoueursMax(string $joueursMax): void
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
