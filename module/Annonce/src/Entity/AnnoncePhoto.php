<?php
namespace Annonce\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Photo d'une annonce. Le fichier est hors de public/ (data/annonces/),
 * servi par le controleur apres controle d'acces.
 *
 * @ORM\Entity
 * @ORM\Table(name="annonce_photo", indexes={
 *     @ORM\Index(name="idx_annonce_photo_annonce", columns={"annonce_id"})
 * })
 */
class AnnoncePhoto
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Annonce\Entity\Annonce", inversedBy="photos")
     * @ORM\JoinColumn(name="annonce_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $annonce;

    /** @ORM\Column(type="string", length=64) */
    private $filename;

    /** @ORM\Column(type="integer") */
    private $position;

    public function __construct(Annonce $annonce, string $filename, int $position)
    {
        $this->annonce = $annonce;
        $this->filename = $filename;
        $this->position = $position;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnnonce(): Annonce
    {
        return $this->annonce;
    }

    public function getFilename(): string
    {
        return (string) $this->filename;
    }

    public function getPosition(): int
    {
        return (int) $this->position;
    }
}
