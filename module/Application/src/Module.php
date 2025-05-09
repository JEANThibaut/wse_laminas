<?php

declare(strict_types=1);

namespace Application;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

public function onBootstrap(MvcEvent $e)
{
    $eventManager = $e->getApplication()->getEventManager();
    
    // Ajouter les headers CORS
    $eventManager->attach(MvcEvent::EVENT_DISPATCH, function($e) {
        $response = $e->getResponse();
        $response->getHeaders()->addHeaders([
            'Access-Control-Allow-Origin' => 'localhost:3000',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            'Access-Control-Allow-Credentials' => 'true',
        ]);

        // Gérer les requêtes préliminaires OPTIONS
        if ($e->getRequest()->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
            return $response;
        }
    }, 100); // Priorité pour l'OPTIONS avant le dispatch

    // 🔹 Ajouter la route actuelle dans le layout
    $routeMatch = $e->getRouteMatch();
    if ($routeMatch) {
        $routeName = $routeMatch->getMatchedRouteName();
        $viewModel = $e->getViewModel();
        $viewModel->setVariable('matchedRouteName', $routeName);
    }

    $eventManager->attach(MvcEvent::EVENT_DISPATCH, function ($e) {
        $serviceManager = $e->getApplication()->getServiceManager();
        $authService = $serviceManager->get(AuthService::class);
        $user = $authService->getIdentity();

        // Ajouter un log pour vérifier ce qui est dans la session
        error_log('Current User from session: ' . print_r($user, true));

        // Ajouter currentUser au layout
        $viewModel = $e->getViewModel();
        $viewModel->setVariable('currentUser', $user);
    }, 100);

}

}
