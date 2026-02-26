<?php

namespace Actus\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Actus\Entity\Actus;


class ActusController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $actusManager;


    public function __construct($entityManager, $authService, $actusManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->actusManager = $actusManager;

    }

    public function actusIndexAction(){
        $actus = $this->entityManager->getRepository(Actus::class)->findAll();
        $currentUser = $this->authService->getIdentity();

        $view = new ViewModel([
            'actus' => $actus,
            'currentUser' => $currentUser,
        ]);

        $this->layout()->setVariable('activeMenu', 'actus-index');
        $view->setTemplate('actus/actus-index');
        return $view;
    }   

    public function actusCreateAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $actus = $this->actusManager->addActus($data);
            if ($actus) {
                $this->flashMessenger()->addSuccessMessage('Actu créée.');
                return $this->redirect()->toRoute('actus-index');
            }
            $this->flashMessenger()->addErrorMessage('Une erreur est survenue.');
        }

        $view = new ViewModel();
        $this->layout()->setVariable('activeMenu', 'actus-index');
        $view->setTemplate('actus/actus-create');
        return $view;
    }

    public function actusEditAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }

        $id = (int) $this->params()->fromRoute('id');
        $actus = $this->entityManager->getRepository(Actus::class)->find($id);

        if (! $actus) {
            $this->flashMessenger()->addErrorMessage('Actu introuvable.');
            return $this->redirect()->toRoute('actus-index');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost()->toArray();
            $this->actusManager->editActus($actus, $data);
            $this->flashMessenger()->addSuccessMessage('Actu modifiée.');
            return $this->redirect()->toRoute('actus-index');
        }

        $view = new ViewModel([
            'actus' => $actus,
        ]);
        $this->layout()->setVariable('activeMenu', 'actus-index');
        $view->setTemplate('actus/actus-edit');
        return $view;
    }

    public function actusDeleteAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }

        $request = $this->getRequest();
        if (! $request->isPost()) {
            return $this->redirect()->toRoute('actus-index');
        }

        $id = (int) $this->params()->fromPost('id');
        $actus = $this->entityManager->getRepository(Actus::class)->find($id);
        if ($actus) {
            $this->actusManager->deleteActus($actus);
            $this->flashMessenger()->addSuccessMessage('Actu supprimée.');
        } else {
            $this->flashMessenger()->addErrorMessage('Actu introuvable.');
        }

        return $this->redirect()->toRoute('actus-index');
    }

    public function actusAdminAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }

        $actus = $this->entityManager->getRepository(Actus::class)->findAll();

        $view = new ViewModel([
            'actus' => $actus,
        ]);

        $this->layout()->setVariable('activeMenu', 'admin-actus');
        $view->setTemplate('actus/actus-admin');
        return $view;
    }


  
}
