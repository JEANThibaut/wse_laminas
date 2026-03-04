<?php
namespace Application\Service;

use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Client;
use Laminas\Http\Request;
use Laminas\Json\Json;
use Throwable;

class SumUpService
{
    protected $authService;
    protected array $config;
    protected string $baseUrl;

    public function __construct(AuthenticationService $authService, array $config)
    {
        $this->authService = $authService;
        $this->config = $config;
        $this->baseUrl = rtrim((string)($this->config['base_url'] ?? 'https://api.sumup.com/v0.1'), '/');
    }

    public function isEnabled(): bool
    {
        return (bool)($this->config['enabled'] ?? false);
    }

    public function isWebhookEnabled(): bool
    {
        return (bool)($this->config['webhook']['enabled'] ?? true);
    }

    public function isReturnConfirmationEnabled(): bool
    {
        return (bool)($this->config['local_test']['confirm_on_return'] ?? false);
    }

    public function hasValidConfiguration(): bool
    {
        return $this->isEnabled()
            && ! empty($this->config['access_token'])
            && ! empty($this->config['merchant_code']);
    }

    public function createCheckout(
        float $amount,
        string $currency,
        string $checkoutReference,
        string $description,
        string $returnUrl,
        ?string $redirectUrl = null
    ): ?array {
        if (! $this->hasValidConfiguration()) {
            return null;
        }

        $client = new Client();
        $client->setUri($this->baseUrl . '/checkouts');
        $client->setMethod(Request::METHOD_POST);
        $client->setHeaders([
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? ''),
            'Content-Type'  => 'application/json',
        ]);

        $params = [
            'checkout_reference' => $checkoutReference,
            'amount'             => $amount,
            'currency'           => $currency,
            'merchant_code'      => $this->config['merchant_code'],
            'description'        => $description,
            'return_url'         => $returnUrl,
        ];

        if ((bool)($this->config['hosted_checkout']['enabled'] ?? true)) {
            $params['hosted_checkout'] = [
                'enabled' => true,
            ];
        }

        if ($redirectUrl !== null && trim($redirectUrl) !== '') {
            $params['redirect_url'] = $redirectUrl;
        }

        try {
            $client->setRawBody(Json::encode($params));
            $response = $client->send();
        } catch (Throwable $exception) {
            return null;
        }

        if (! $response->isSuccess()) {
            return null;
        }

        return Json::decode($response->getBody(), Json::TYPE_ARRAY);
    }

    public function getCheckoutByReference(string $checkoutReference): ?array
    {
        if (! $this->hasValidConfiguration()) {
            return null;
        }

        $client = new Client();
        $client->setUri($this->baseUrl . '/checkouts');
        $client->setMethod(Request::METHOD_GET);
        $client->setHeaders([
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? ''),
        ]);
        $client->setParameterGet([
            'checkout_reference' => $checkoutReference,
        ]);

        try {
            $response = $client->send();
        } catch (Throwable $exception) {
            return null;
        }

        if (! $response->isSuccess()) {
            return null;
        }

        $data = Json::decode($response->getBody(), Json::TYPE_ARRAY);
        if (! is_array($data) || ! isset($data[0]) || ! is_array($data[0])) {
            return null;
        }

        return $data[0];
    }

    public function getCheckoutById(string $checkoutId): ?array
    {
        if (! $this->hasValidConfiguration() || $checkoutId === '') {
            return null;
        }

        $client = new Client();
        $client->setUri($this->baseUrl . '/checkouts/' . rawurlencode($checkoutId));
        $client->setMethod(Request::METHOD_GET);
        $client->setHeaders([
            'Authorization' => 'Bearer ' . ($this->config['access_token'] ?? ''),
        ]);

        try {
            $response = $client->send();
        } catch (Throwable $exception) {
            return null;
        }

        if (! $response->isSuccess()) {
            return null;
        }

        $data = Json::decode($response->getBody(), Json::TYPE_ARRAY);
        if (! is_array($data)) {
            return null;
        }

        return $data;
    }
}