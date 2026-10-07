<?php
namespace Application\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Question de la FAQ, modifiable par le GOD (GOD MODE > FAQ).
 *
 * La reponse est du texte simple (retours a la ligne respectes) ou du HTML
 * (carte, tableau, liens...) : seul le GOD peut l'ecrire.
 *
 * @ORM\Entity
 * @ORM\Table(name="faq_item", indexes={
 *     @ORM\Index(name="idx_faq_item_position", columns={"position"})
 * })
 */
class FaqItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $question;

    /**
     * @ORM\Column(type="text")
     */
    private $answer;

    /**
     * Ordre d'affichage, croissant.
     *
     * @ORM\Column(type="integer")
     */
    private $position = 0;

    /**
     * Masquee : conservee mais absente de la page FAQ.
     *
     * @ORM\Column(name="is_active", type="boolean", options={"default": 1})
     */
    private $isActive = true;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    public function __construct(string $question, string $answer, int $position)
    {
        $this->position = $position;
        $this->update($question, $answer, true);
    }

    public function update(string $question, string $answer, bool $isActive): void
    {
        $this->question = $question;
        $this->answer = $answer;
        $this->isActive = $isActive;
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    /**
     * La reponse contient-elle du HTML ? Sinon, c'est du texte simple a
     * echapper, retours a la ligne compris.
     */
    public function isHtml(): bool
    {
        return $this->answer !== strip_tags($this->answer);
    }

    public function getPosition(): int
    {
        return (int) $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isActive(): bool
    {
        return (bool) $this->isActive;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
