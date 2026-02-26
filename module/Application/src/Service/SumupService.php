<?php
namespace Application\Service;

use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Json\Json;

class SumUpService
{
    protected $authService;
    protected array $config;
    protected string $baseUrl = 'https://api.sumup.com/v1.0';

    public function __construct(AuthenticationService $authService, array $config)
    {
        $this->authService = $authService;
        $this->config = $config;
    }

    public function createCheckout(float $amount, string $currency, string $orderId, string $bbWeight)
    {
        // Récupération automatique de l'email si l'utilisateur est logué
        $email = 'guest@example.com';
        if ($this->authService->hasIdentity()) {
            $identity = $this->authService->getIdentity();
            // Adapte selon ton objet Identity (ex: $identity->getEmail())
            $email = is_object($identity) ? $identity->getEmail() : $identity;
        }

        $client = new Client();
        $client->setUri($this->baseUrl . '/checkouts');
        $client->setMethod(Request::METHOD_POST);
        $client->setHeaders([
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? ''),
            'Content-Type'  => 'application/json',
        ]);

        $params = [
            'checkout_reference' => $orderId,
            'amount'             => $amount,
            'currency'           => $currency,
            'pay_to_email'       => $email,
            'description'        => "Commande Billes : " . $bbWeight . "g",
        ];

        $client->setRawBody(Json::encode($params));
        $response = $client->send();

        return $response->isSuccess() ? Json::decode($response->getBody(), Json::TYPE_ARRAY) : null;
    }
}