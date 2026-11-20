<?php
namespace Annonce\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Version des CGU de l'espace Petites annonces. La plus haute est en
 * vigueur ; chaque membre doit l'accepter une fois. Texte en HTML (seul le
 * GOD l'ecrit). Une version publiee n'est jamais modifiee : c'est la preuve
 * de ce qui a ete accepte.
 *
 * @ORM\Entity
 * @ORM\Table(name="annonce_cgu_version", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniq_annonce_cgu_version", columns={"version"})
 * })
 */
class CguVersion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /** @ORM\Column(type="integer") */
    private $version;

    /** @ORM\Column(type="text") */
    private $content;

    /** @ORM\Column(name="published_at", type="datetime") */
    private $publishedAt;

    public function __construct(int $version, string $content)
    {
        $this->version = $version;
        $this->content = $content;
        $this->publishedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return (int) $this->version;
    }

    public function getContent(): string
    {
        return (string) $this->content;
    }

    public function getPublishedAt(): \DateTime
    {
        return $this->publishedAt;
    }
}
