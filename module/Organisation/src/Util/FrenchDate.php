<?php
namespace Organisation\Util;

/**
 * Dates en toutes lettres, sans dependre de l'extension intl :
 * "samedi 28 novembre 2026 à 14h00".
 */
final class FrenchDate
{
    public const DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
    public const DAYS_SHORT = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
    public const MONTHS = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    public static function day(\DateTimeInterface $date): string
    {
        return self::DAYS[(int) $date->format('N') - 1] . ' ' . (int) $date->format('j') . ' '
            . self::MONTHS[(int) $date->format('n') - 1] . ' ' . $date->format('Y');
    }

    public static function dayTime(\DateTimeInterface $date): string
    {
        return self::day($date) . ' à ' . $date->format('H\hi');
    }

    public static function month(\DateTimeInterface $date): string
    {
        return ucfirst(self::MONTHS[(int) $date->format('n') - 1]) . ' ' . $date->format('Y');
    }

    /**
     * Debut et fin d'un evenement : "samedi 28 novembre 2026, 9h00 – 17h00"
     * (meme jour) ou "du ... au ..." (plusieurs jours).
     */
    public static function range(\DateTimeInterface $start, ?\DateTimeInterface $end): string
    {
        if (!$end) {
            return self::dayTime($start);
        }
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return self::day($start) . ', ' . $start->format('H\hi') . ' – ' . $end->format('H\hi');
        }
        return 'du ' . self::dayTime($start) . ' au ' . self::dayTime($end);
    }
}
