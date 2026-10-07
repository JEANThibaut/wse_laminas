<?php
namespace Application\Service;

use GuzzleHttp\Client;
use User\Entity\User;

/**
 * Publication sur la Page Facebook de l'association (Graph API de Meta).
 *
 * Le jeton configure est celui d'un utilisateur systeme du portefeuille
 * business (n'expire pas, ne depend d'aucun compte personnel) : on en derive
 * a chaque envoi le jeton de la Page, seul habilite a publier en son nom.
 *
 * Tant que 'live' vaut false, les publications sont creees NON publiees :
 * visibles des seuls admins de la Page, pour tester sans rien montrer au public.
 * En phase de test, seul le GOD peut publier.
 */
class FacebookPublisher
{
    private const GRAPH_URL = 'https://graph.facebook.com/';

    private string $pageId;
    private string $token;
    private bool $live;
    private string $graphVersion;
    private string $template;
    private string $siteUrl;
    private ?Client $client;

    /**
     * @param Client|null $client client HTTP vers l'API de Meta (remplace dans les tests)
     */
    public function __construct(array $config, string $siteUrl, ?Client $client = null)
    {
        $this->pageId = trim((string) ($config['page_id'] ?? ''));
        $this->token = trim((string) ($config['token'] ?? ''));
        $this->live = (bool) ($config['live'] ?? false);
        $this->graphVersion = (string) ($config['graph_version'] ?? 'v21.0');
        $this->template = (string) ($config['template'] ?? '');
        $this->siteUrl = rtrim($siteUrl, '/');
        $this->client = $client;
    }

    public function isConfigured(): bool
    {
        return $this->pageId !== '' && $this->token !== '';
    }

    /**
     * Le compte peut-il publier ? (GOD seulement en phase de test)
     */
    public function canPublish(?User $user): bool
    {
        return $this->isConfigured() && $user !== null && $user->isGod();
    }

    public function isLive(): bool
    {
        return $this->live;
    }

    /**
     * Modele de message, avec les marqueurs {DATE}, {jour}, {date}, {places} et {lien}.
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Remplace les marqueurs du message par les informations de la partie.
     */
    public function renderMessage(string $message, \DateTimeInterface $date, int $places): string
    {
        $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août',
            'septembre', 'octobre', 'novembre', 'décembre'];
        $day = $days[(int) $date->format('w')];

        return strtr($message, [
            // "DIMANCHE 11 OCTOBRE"
            '{DATE}' => mb_strtoupper($day . ' ' . ($date->format('j') === '1' ? '1er' : $date->format('j')) . ' ' . $months[(int) $date->format('n') - 1]),
            '{jour}' => $day,
            '{date}' => $date->format('d/m'),
            '{places}' => (string) $places,
            '{lien}' => $this->siteUrl,
        ]);
    }

    /**
     * Publie le message sur la Page, avec un lien vers le site.
     *
     * @return array{success: bool, url: ?string, published: bool, error: ?string}
     */
    public function publish(string $message): array
    {
        $result = ['success' => false, 'url' => null, 'published' => $this->live, 'error' => null];
        if (!$this->isConfigured()) {
            $result['error'] = "La Page Facebook n'est pas configurée sur ce serveur.";
            return $result;
        }

        $client = $this->client
            ?? new Client(['base_uri' => self::GRAPH_URL . $this->graphVersion . '/', 'timeout' => 15, 'http_errors' => false]);
        try {
            // 1. Jeton de la Page, derive du jeton de l'utilisateur systeme
            $page = $this->decode($client->get($this->pageId, [
                'query' => ['fields' => 'access_token', 'access_token' => $this->token],
            ]));
            if (empty($page['access_token'])) {
                $result['error'] = $this->errorMessage($page, "Impossible d'obtenir l'accès à la Page.");
                return $result;
            }

            // 2. Publication, ou brouillon tant que live = false. Sans
            // unpublished_content_type, Facebook range une publication non
            // publiee dans les publications publicitaires : DRAFT la met dans
            // les brouillons de Business Suite (a relire, publier ou supprimer).
            $params = [
                'message' => $message,
                'link' => $this->siteUrl . '/',
                'published' => $this->live ? 'true' : 'false',
                'access_token' => $page['access_token'],
            ];
            if (!$this->live) {
                $params['unpublished_content_type'] = 'DRAFT';
            }
            $post = $this->decode($client->post($this->pageId . '/feed', ['form_params' => $params]));
            if (empty($post['id'])) {
                $result['error'] = $this->errorMessage($post, 'La publication a été refusée par Facebook.');
                return $result;
            }

            // L'id renvoye est "<idPage>_<idPublication>"
            [$pagePart, $postPart] = array_pad(explode('_', (string) $post['id'], 2), 2, '');
            $result['success'] = true;
            $result['url'] = 'https://www.facebook.com/' . $pagePart . '/posts/' . $postPart;
        } catch (\Throwable $e) {
            error_log('Facebook : ' . $e->getMessage());
            $result['error'] = 'Facebook est injoignable pour le moment.';
        }
        return $result;
    }

    private function decode($response): array
    {
        $data = json_decode((string) $response->getBody(), true);
        return is_array($data) ? $data : [];
    }

    private function errorMessage(array $data, string $fallback): string
    {
        $message = $data['error']['message'] ?? null;
        if ($message) {
            // Jamais le jeton dans le journal ni a l'ecran : seul le message de Meta
            error_log('Facebook : ' . $message);
            return $fallback . ' (' . $message . ')';
        }
        return $fallback;
    }
}
