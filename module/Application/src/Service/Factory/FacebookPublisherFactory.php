<?php
namespace Application\Service\Factory;

use Application\Service\FacebookPublisher;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FacebookPublisherFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');

        return new FacebookPublisher(
            // page_id et token : config/autoload/facebook.global.php, genere au
            // deploiement depuis les secrets GitHub, jamais dans le repo
            $config['facebook'] ?? [],
            (string) ($config['queue']['site_url'] ?? 'https://www.wolfsofteure.fr')
        );
    }
}
