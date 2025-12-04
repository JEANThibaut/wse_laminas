<?php

namespace Forum\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="forum_answer")
 */
class ForumAnswer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type="integer", name="subject_id") */
    private $subjectId;

    /** @ORM\Column(type="longtext") */
    private $content;

    /** @ORM\Column(type="integer", name="user_id") */
    private $userId;

    /** @ORM\Column(type="datetime") */
    private $date;

    public function getId()
    {
        return $this->id;
    }

    public function getSubjectId()
    {
        return $this->subjectId;
    }

    public function setSubjectId($subjectId): self
    {
        $this->subjectId = $subjectId;
        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId): self
    {
        $this->userId = $userId;
        return $this;
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
}