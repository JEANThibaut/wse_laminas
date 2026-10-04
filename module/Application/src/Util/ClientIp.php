<?php
namespace Application\Util;

/**
 * IP reelle du client, y compris derriere le proxy de l'hebergeur.
 *
 * REMOTE_ADDR est l'IP de la machine qui a ouvert la connexion : le client,
 * ou un proxy. Si c'est un proxy (IP privee, ou IP declaree dans
 * $trustedProxies), l'IP du client est dans X-Forwarded-For. Cet en-tete se
 * lit de droite a gauche : chaque proxy ajoute a la fin l'IP qu'il a vue, et
 * seules les entrees ajoutees par nos proxies sont fiables (le debut de la
 * liste est fourni par le client et peut etre invente).
 */
class ClientIp
{
    /**
     * @param array<string, mixed> $server         $_SERVER ou equivalent
     * @param string[]             $trustedProxies IP ou plages CIDR de proxies publics a croire
     */
    public static function resolve(array $server, array $trustedProxies = []): string
    {
        $remote = trim((string) ($server['REMOTE_ADDR'] ?? ''));
        if (!self::isProxy($remote, $trustedProxies)) {
            return $remote;
        }

        $forwarded = array_filter(array_map('trim', explode(',', (string) ($server['HTTP_X_FORWARDED_FOR'] ?? ''))));
        foreach (array_reverse($forwarded) as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                break;
            }
            if (!self::isProxy($ip, $trustedProxies)) {
                return $ip;
            }
        }

        // Aucune IP exploitable apres nos proxies : mieux vaut l'IP du proxy que rien
        return $remote;
    }

    private static function isProxy(string $ip, array $trustedProxies): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        // Reseau prive ou reserve : forcement une machine de l'hebergeur
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        foreach ($trustedProxies as $range) {
            if (self::inRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private static function inRange(string $ip, string $range): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $range, 2), 2, null);
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
        $bits = $bits === null ? strlen($ipBin) * 8 : (int) $bits;

        $bytes = intdiv($bits, 8);
        if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }
}
