<?php
namespace Annonce\Service;

use Annonce\Entity\AnnonceSetting;
use Doctrine\ORM\EntityManager;

/**
 * Reglages de l'espace Petites annonces, modifiables dans GOD MODE.
 * Reglage absent de la table : valeur par defaut ci-dessous.
 */
class AnnonceSettings
{
    // Anciennete minimale (jours depuis la validation de l'email) pour publier sans avoir joue
    public const MIN_DAYS = 'min_days';
    // Annonces en ligne en meme temps par membre
    public const MAX_ONLINE = 'max_online';
    // Duree de vie d'une annonce (jours)
    public const DURATION_DAYS = 'duration_days';
    // Renouvellements possibles par annonce
    public const MAX_RENEW = 'max_renew';
    // Le renouvellement est propose ce nombre de jours avant l'expiration
    public const RENEW_WINDOW_DAYS = 'renew_window_days';
    // Suppression definitive (annonce et photos) ce nombre de jours apres expiration ou retrait
    public const PURGE_DAYS = 'purge_days';
    // Signalements de membres differents qui masquent une annonce en attendant la moderation
    public const REPORT_THRESHOLD = 'report_threshold';
    // Duree proposee pour une interdiction temporaire de publier (jours)
    public const SUSPENSION_DAYS = 'suspension_days';
    // Date de la derniere purge automatique (interne)
    public const LAST_PURGE = 'last_purge';

    /**
     * Reglages entiers modifiables : cle => [libelle, defaut, minimum, maximum].
     */
    public const EDITABLE = [
        self::MIN_DAYS => ["Ancienneté minimale pour publier sans avoir joué (jours)", 30, 0, 365],
        self::MAX_ONLINE => ['Annonces en ligne par membre', 3, 1, 20],
        self::DURATION_DAYS => ["Durée de vie d'une annonce (jours)", 30, 1, 365],
        self::MAX_RENEW => ['Renouvellements possibles par annonce', 1, 0, 10],
        self::RENEW_WINDOW_DAYS => ["Renouvellement proposé avant l'expiration (jours)", 7, 0, 60],
        self::PURGE_DAYS => ['Suppression définitive après expiration ou retrait (jours)', 30, 1, 365],
        self::REPORT_THRESHOLD => ['Signalements avant masquage automatique', 3, 1, 20],
        self::SUSPENSION_DAYS => ['Durée proposée pour une interdiction temporaire (jours)', 30, 1, 365],
    ];

    private EntityManager $entityManager;

    /** @var array<string, AnnonceSetting>|null */
    private ?array $rows = null;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getInt(string $key): int
    {
        $row = $this->rows()[$key] ?? null;
        $default = self::EDITABLE[$key][1] ?? 0;
        return $row !== null ? (int) $row->getValue() : $default;
    }

    public function get(string $key): ?string
    {
        $row = $this->rows()[$key] ?? null;
        return $row ? $row->getValue() : null;
    }

    /**
     * Enregistre une valeur ; un entier modifiable est borne a son minimum / maximum.
     */
    public function set(string $key, string $value): void
    {
        if (isset(self::EDITABLE[$key])) {
            [, , $min, $max] = self::EDITABLE[$key];
            $value = (string) max($min, min($max, (int) $value));
        }
        $rows = $this->rows();
        if (isset($rows[$key])) {
            $rows[$key]->setValue($value);
        } else {
            $row = new AnnonceSetting($key, $value);
            $this->entityManager->persist($row);
            $this->rows[$key] = $row;
        }
        $this->entityManager->flush();
    }

    /**
     * @return array<string, AnnonceSetting>
     */
    private function rows(): array
    {
        if ($this->rows === null) {
            $this->rows = [];
            foreach ($this->entityManager->getRepository(AnnonceSetting::class)->findAll() as $row) {
                $this->rows[$row->getKey()] = $row;
            }
        }
        return $this->rows;
    }
}
