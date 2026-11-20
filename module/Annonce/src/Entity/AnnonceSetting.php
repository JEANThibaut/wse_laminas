<?php
namespace Annonce\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Reglage de l'espace Petites annonces (cle / valeur). Les cles et leurs
 * valeurs par defaut sont dans Annonce\Service\AnnonceSettings.
 *
 * @ORM\Entity
 * @ORM\Table(name="annonce_setting")
 */
class AnnonceSetting
{
    /**
     * @ORM\Id
     * @ORM\Column(name="setting_key", type="string", length=50)
     */
    private $key;

    /** @ORM\Column(name="setting_value", type="string", length=255) */
    private $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return (string) $this->key;
    }

    public function getValue(): string
    {
        return (string) $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }
}
