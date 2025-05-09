<?php
namespace Application\Plugin;

use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class ValidateApiKey extends AbstractPlugin
{
    private const VALID_API_KEYS = [
        'myKey', 
    ];

    /**
     * Vérifie si l'API Key est valide.
     *
     * @param AbstractActionController $controller Instance du contrôleur appelant.
     * @return JsonModel|null Retourne un modèle JSON en cas d'échec ou null si tout est valide.
     */
    public function __invoke(AbstractActionController $controller): ?JsonModel
    {
        $request = $controller->getRequest();

        // Vérifiez si la requête est une instance de HttpRequest
        if (!$request instanceof HttpRequest) {
            return new JsonModel([
                'success' => false,
                'message' => 'Invalid request type',
            ]);
        }

        $headers = $request->getHeaders();

        if ($headers->has('apiKey')) {
            $apiKey = $headers->get('apiKey')->getFieldValue();
        } else {
            $apiKey = null;
        }

        if (!in_array($apiKey, self::VALID_API_KEYS, true)) {
            return new JsonModel([
                'success' => false,
                'message' => 'Invalid or missing API Key',
            ]);
        }

        return null; // Tout est OK
    }
}
