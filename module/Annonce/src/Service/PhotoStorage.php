<?php
namespace Annonce\Service;

/**
 * Fichiers des photos d'annonces, hors de public/ : data/annonces/<id annonce>/.
 *
 * Chaque photo envoyee est decodee puis reencodee en JPEG (1600 px maximum) :
 * les metadonnees EXIF, dont la position GPS, disparaissent, et un fichier
 * pieges ne survit pas au reencodage. Le navigateur redimensionne deja avant
 * l'envoi (public/js/annonce-photos.js), ce qui convertit aussi le HEIC des
 * iPhone ; le serveur, lui, ne lit que JPEG, PNG, WebP et GIF.
 *
 * Le dossier est exclu du deploiement (.github/deploy-excludes.txt) et
 * protege par un .htaccess, au cas ou il serait sous la racine web.
 */
class PhotoStorage
{
    public const MAX_SIDE = 1600;
    public const MAX_UPLOAD_BYTES = 15 * 1024 * 1024;
    private const JPEG_QUALITY = 82;
    private const BASE_DIR = 'data/annonces';

    /**
     * Enregistre une photo envoyee (entree de $_FILES normalisee).
     *
     * @return string nom du fichier enregistre
     * @throws \RuntimeException message affichable au membre
     */
    public function store(int $annonceId, array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new \RuntimeException('Photo trop lourde.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($error !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException("La photo n'a pas pu être envoyée.");
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            throw new \RuntimeException('Photo trop lourde (15 Mo maximum).');
        }

        $info = @getimagesize($tmp);
        $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
        if (!$info || !in_array($info[2], $allowed, true)) {
            throw new \RuntimeException('Format de photo non pris en charge (JPEG, PNG ou WebP).');
        }
        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width * $height > 50000000) {
            throw new \RuntimeException('Dimensions de photo non prises en charge.');
        }

        // Une photo de telephone non redimensionnee peut demander beaucoup de memoire une fois decodee
        @ini_set('memory_limit', '512M');
        $source = @imagecreatefromstring((string) file_get_contents($tmp));
        if (!$source) {
            throw new \RuntimeException('Photo illisible.');
        }

        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($newWidth, $newHeight);
        // Fond blanc pour les images transparentes (PNG, GIF, WebP)
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        unset($source);

        $dir = $this->annonceDir($annonceId, true);
        $filename = bin2hex(random_bytes(16)) . '.jpg';
        $saved = imagejpeg($target, $dir . '/' . $filename, self::JPEG_QUALITY);
        unset($target);
        if (!$saved) {
            throw new \RuntimeException("La photo n'a pas pu être enregistrée.");
        }
        return $filename;
    }

    /**
     * Chemin du fichier, ou null s'il n'existe pas.
     */
    public function path(int $annonceId, string $filename): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}\.jpg$/', $filename)) {
            return null;
        }
        $path = $this->annonceDir($annonceId, false) . '/' . $filename;
        return is_file($path) ? $path : null;
    }

    public function delete(int $annonceId, string $filename): void
    {
        $path = $this->path($annonceId, $filename);
        if ($path !== null) {
            @unlink($path);
        }
    }

    /**
     * Supprime toutes les photos d'une annonce et son dossier.
     */
    public function deleteAll(int $annonceId): void
    {
        $dir = $this->annonceDir($annonceId, false);
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*.jpg') ?: [] as $path) {
            @unlink($path);
        }
        @rmdir($dir);
    }

    private function annonceDir(int $annonceId, bool $create): string
    {
        $dir = self::BASE_DIR . '/' . $annonceId;
        if ($create) {
            $this->ensureBaseDir();
            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new \RuntimeException("La photo n'a pas pu être enregistrée.");
            }
        }
        return $dir;
    }

    private function ensureBaseDir(): void
    {
        if (!is_dir(self::BASE_DIR) && !@mkdir(self::BASE_DIR, 0775, true) && !is_dir(self::BASE_DIR)) {
            throw new \RuntimeException("La photo n'a pas pu être enregistrée.");
        }
        $htaccess = self::BASE_DIR . '/.htaccess';
        if (!is_file($htaccess)) {
            @file_put_contents($htaccess, "# Photos servies par l'application uniquement\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n");
        }
    }
}
