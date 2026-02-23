<?php
namespace Actus;

class Module
{
    /**
     * Retourne la configuration du module (module.config.php)
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
