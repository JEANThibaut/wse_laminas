<?php
namespace Faction\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Faction\Service\FactionManager;
use Faction\Entity\Faction;
use Faction\Entity\Objectif;

class FactionController extends AbstractActionController
{
    private $factionManager;
    private $authService;

        private $entityManager;

    public function __construct(FactionManager $factionManager, $authService,$entityManager)
    {
        $this->factionManager = $factionManager;
        $this->authService = $authService;
        $this->entityManager = $entityManager;

        
    }

    /**
     * Override onDispatch to set a module-specific layout and layout variables
     */
    public function onDispatch(\Laminas\Mvc\MvcEvent $e)
    {
        // use the faction-specific layout
        $this->layout('layout/faction');
        $this->layout()->setVariable('hideMenu', true);

        return parent::onDispatch($e);
    }

    public function indexAction()
    {
        $currentUser = $this->authService->getIdentity();
        $faction = $this->entityManager->getRepository(Faction::class)->findOneBy(['idfaction' => $currentUser->getFaction()]);
        $objectifs = $this->entityManager->getRepository(Objectif::class)->findBy(['idfaction' => $currentUser->getFaction()]);

        $view = new ViewModel([
            'currentUser' => $currentUser,
            'faction'     => $faction,
            'objectifs'  => $objectifs
        ]);

        $this->layout()->setVariable('activeMenu', 'Faction-index');
        $view->setTemplate('faction/index');
        return $view;


    }
}
