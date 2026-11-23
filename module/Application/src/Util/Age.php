<?php

namespace Application\Util;

/**
 * Majorite : le site est reserve aux personnes majeures (les mineurs ne
 * jouent pas sur le terrain, cf. FAQ). Controle a l'inscription et a la
 * modification du profil ; declaratif, comme la date de naissance.
 */
final class Age
{
    public const ADULT = 18;
    public const MINOR_MESSAGE = 'Le site est réservé aux personnes majeures.';

    public static function isAdult(\DateTimeInterface $birthdate, ?\DateTimeInterface $today = null): bool
    {
        $today ??= new \DateTimeImmutable('today');
        return $birthdate <= (new \DateTimeImmutable($today->format('Y-m-d')))->modify('-' . self::ADULT . ' years');
    }

    /**
     * Date de naissance la plus recente acceptee aujourd'hui (Y-m-d), pour les formulaires.
     */
    public static function latestBirthdate(): string
    {
        return (new \DateTimeImmutable('today'))->modify('-' . self::ADULT . ' years')->format('Y-m-d');
    }
}
