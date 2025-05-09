<?php
namespace Application\Plugin\Factory;

use Application\Plugin\ValidateApiKey;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class ValidateApiKeyFactory
{
    public function __invoke(ContainerInterface $container): ValidateApiKey
    {
        $request = $container->get(ServerRequestInterface::class);
        return new ValidateApiKey($request);
    }
}
