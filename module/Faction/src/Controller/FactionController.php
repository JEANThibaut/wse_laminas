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

        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $currentUser = $this->authService->getIdentity();
        $faction = $this->entityManager->getRepository(Faction::class)->findOneBy(['idfaction' => $currentUser->getFaction()]);
        $objectifs = $this->entityManager->getRepository(Objectif::class)->findBy(['idfaction' => $currentUser->getFaction()]);
        if (!$faction) {
            // Redirect using a literal URL to avoid route name resolution issues
            return $this->redirect()->toUrl('/faction-register');
        }
        
        $view = new ViewModel([
            'currentUser' => $currentUser,
            'faction'     => $faction,
            'objectifs'  => $objectifs
        ]);


        $this->layout()->setVariable('activeMenu', 'Faction-index');
        $view->setTemplate('faction/index');
        return $view;

    }


    public function factionRegisterAction(){
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }        
        $currentUser = $this->authService->getIdentity();
        $view = new ViewModel([
            'currentUser' => $currentUser,
        ]);
        $request = $this->getRequest(); 
        if($request->isPost()){
            $data = json_decode($request->getContent(), true);
            $factionId = $data['factionId'] ?? null;
            $faction = $this->entityManager->getRepository(Faction::class)->findOneBy(['idfaction' => $factionId]);
            $result = $this->factionManager->joinFaction($faction,$currentUser,);
            if ($result) {
                return $this->redirect()->toUrl('/faction');
            } else {
                return $this->redirect()->toUrl('/faction-register');
            }
        }
        // Template file is located at module/Faction/view/faction/faction-register.phtml
        // Set explicit template to match that location (module folder + template file)
        $view->setTemplate('faction/faction-register');
        return $view;
    }
}
