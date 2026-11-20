<?php
namespace Annonce\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tag de recherche des annonces, gere dans GOD MODE > Petites annonces.
 * Un tag deja utilise n'est jamais supprime, seulement desactive : il
 * reste sur les annonces existantes mais n'est plus propose.
 *
 * @ORM\Entity
 * @ORM\Table(name="annonce_tag", indexes={
 *     @ORM\Index(name="idx_annonce_tag_position", columns={"position"})
 * })
 */
class AnnonceTag
{
    public const LABEL_MAX = 50;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type="string", length=50) */
    private $label;

    /** @ORM\Column(type="integer") */
    private $position;

    /** @ORM\Column(name="is_active", type="boolean", options={"default": 1}) */
    private $isActive = true;

    public function __construct(string $label, int $position)
    {
        $this->label = $label;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return (string) $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getPosition(): int
    {
        return (int) $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function isActive(): bool
    {
        return (bool) $this->isActive;
    }

    public function setActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}
