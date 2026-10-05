<?php
namespace Application\Controller;

use Application\Service\AuthService;
use Application\Service\PushService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

/**
 * Abonnement / desabonnement d'un appareil aux notifications, appele en
 * JSON par public/js/push.js. Reserve aux comptes autorises (PwaAccessPolicy).
 */
class PushController extends AbstractActionController
{
    private AuthService $authService;
    private PushService $pushService;

    public function __construct(AuthService $authService, PushService $pushService)
    {
        $this->authService = $authService;
        $this->pushService = $pushService;
    }

    public function subscribeAction()
    {
        [$user, $data, $error] = $this->readRequest();
        if ($error) {
            return $error;
        }
        $endpoint = (string) ($data['endpoint'] ?? '');
        $p256dh = (string) ($data['keys']['p256dh'] ?? '');
        $auth = (string) ($data['keys']['auth'] ?? '');
        if (!str_starts_with($endpoint, 'https://') || strlen($endpoint) > 2048 || $p256dh === '' || $auth === ''
            || strlen($p256dh) > 255 || strlen($auth) > 255) {
            return $this->json(400, ['error' => 'Abonnement invalide.']);
        }

        $this->pushService->subscribe($user, $endpoint, $p256dh, $auth, $this->getRequest()->getServer('HTTP_USER_AGENT'));
        return $this->json(200, ['ok' => true]);
    }

    public function unsubscribeAction()
    {
        [$user, $data, $error] = $this->readRequest();
        if ($error) {
            return $error;
        }
        $this->pushService->unsubscribe($user, (string) ($data['endpoint'] ?? ''));
        return $this->json(200, ['ok' => true]);
    }

    /**
     * Interrupteur d'une categorie dans le profil : { category, enabled }.
     */
    public function preferencesAction()
    {
        [$user, $data, $error] = $this->readRequest();
        if ($error) {
            return $error;
        }
        $category = (string) ($data['category'] ?? '');
        if (!array_key_exists($category, PushService::CATEGORY_LABELS) || !isset($data['enabled'])) {
            return $this->json(400, ['error' => 'Préférence invalide.']);
        }
        try {
            $this->pushService->setPreference($user, $category, (bool) $data['enabled']);
        } catch (\RuntimeException $e) {
            return $this->json(503, ['error' => $e->getMessage()]);
        }
        return $this->json(200, ['ok' => true, 'preferences' => $this->pushService->getPreferences($user)]);
    }

    /**
     * @return array{0: ?\User\Entity\User, 1: array, 2: ?JsonModel}
     */
    private function readRequest(): array
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return [null, [], $this->json(405, ['error' => 'Méthode non autorisée.'])];
        }
        $user = $this->authService->getIdentity();
        if (!$this->pushService->canUse($user)) {
            return [null, [], $this->json(403, ['error' => 'Notifications non disponibles pour ce compte.'])];
        }
        $data = json_decode((string) $request->getContent(), true);
        return [$user, is_array($data) ? $data : [], null];
    }

    private function json(int $status, array $data): JsonModel
    {
        $this->getResponse()->setStatusCode($status);
        return new JsonModel($data);
    }
}
