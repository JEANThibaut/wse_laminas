<?php

declare(strict_types=1);

namespace Forum\Entity;

use Doctrine\ORM\Mapping as ORM;
use Users\Entity\Users;

/**
  * @ORM\Entity(repositoryClass="Forum\Repository\ForumRepository")
 * @ORM\Table(name="forum_topic")
 */
class Topic
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="text")
     */
    private int $title;

    /**
     * @ORM\Column(type="integer")
     */
    private int $createur;

    /**
     * @ORM\Column(type="integer")
     */
    private int $date_creation;

    // Getters et Setters
    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getCreateur()
    {
        return $this->createur;
    }
    public function setCreateur($createur)
    {
        $this->createur = $createur;
    }
    
    public function getDateCreation()
    {
        return $this->date_creation;
    }
    public function setDateCreation($date_creation)
    {
        $this->date_creation = $date_creation;
    }

}

