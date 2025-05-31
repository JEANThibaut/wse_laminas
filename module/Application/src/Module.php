<?php

declare(strict_types=1);

namespace Application;

use Laminas\Mvc\MvcEvent;
use Laminas\Authentication\AuthenticationService;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SessionManager;
use Laminas\Session\Container;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    
    public function onBootstrap(MvcEvent $e): void
    {
        $application = $e->getApplication();
        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions([
            'cookie_lifetime' => 604800, // 7 jours
            'gc_maxlifetime'  => 604800,
        ]);

        $sessionManager = new SessionManager($sessionConfig);
        $sessionManager->start();
        Container::setDefaultManager($sessionManager);


        $eventManager = $application->getEventManager();
    
        $eventManager->attach(MvcEvent::EVENT_RENDER, function (MvcEvent $e) {
            $serviceManager = $e->getApplication()->getServiceManager();
            $authService = $serviceManager->get(AuthenticationService::class);
            $user = $authService->getIdentity();
    
            // Récupère le layout (ViewModel racine)
            $layout = $e->getViewModel();
            $layout->setVariable('currentUser', $user);
    
            // Propager dans toutes les vues enfants
            foreach ($layout->getChildren() as $child) {
                $child->setVariable('currentUser', $user);
            }
        }, 100);
    }
    
    
}
