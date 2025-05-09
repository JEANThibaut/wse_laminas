<?php

declare(strict_types=1);

namespace Forum\Entity;

use Doctrine\ORM\Mapping as ORM;
use Users\Entity\Users;

/**
  * @ORM\Entity(repositoryClass="Forum\Repository\ForumRepository")
 * @ORM\Table(name="forum_message")
 */
class Message
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
    private int $content;

    /**
     * @ORM\Column(type="integer")
     */
    private int $topic;

    /**
     * @ORM\Column(type="integer")
     */
    private int $auteur;

    /**
     * @ORM\Column(type="integer")
     */
    private int $date_creation;

    // Getters et Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function getContent()
    {
        return $this->content;
    }
    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getTopic()
    {
        return $this->topic;
    }
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    public function getAuteur()
    {
        return $this->auteur;
    }
    public function setAuteur($auteur)
    {
        $this->auteur = $auteur;
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

