<?php
namespace Game;

use Game\Service\QueueManager;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\MvcEvent;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(MvcEvent $e): void
    {
        // Avant chaque action : les places proposees a la file d'attente dont
        // le delai est depasse passent au joueur suivant. Sans tache planifiee
        // sur l'hebergement, c'est la visite du site qui fait avancer la file.
        // Une requete legere, qui ne modifie rien s'il n'y a rien a expirer.
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function (MvcEvent $e) {
            try {
                $e->getApplication()->getServiceManager()->get(QueueManager::class)->processExpiredOffers();
            } catch (\Throwable $error) {
                // Table pas encore creee, base indisponible... : jamais bloquant pour la page
                error_log('file d\'attente, expiration : ' . $error->getMessage());
            }
        }, 100);
    }
}
