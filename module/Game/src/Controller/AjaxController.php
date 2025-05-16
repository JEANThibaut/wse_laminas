<?php
namespace Game\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Game\Service\RepliqueManager;
use Game\Entity\Replique;
use Game\Form\RepliqueForm;


class AjaxController extends AbstractActionController
{
    private $authService;
    private $repliqueManager;
    private $entityManager;

    public function __construct($entityManager,$authService, $repliqueManager)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->repliqueManager = $repliqueManager;
    }

    
    // public function ajaxGetRepliquesAction()
    // {
        
    //     $currentUser = $this->authService->getIdentity();
    //     $arsenal = $this->entityManager->getRepository(Replique::class)->findBy(['iduser'=>$currentUser->getIduser()]);
    //     $data = [];
    
    //     foreach ($arsenal as $replique) {
    //         $data[] = [
    //             'nom'       => $replique->getNomReplique(),
    //             'type'      => $replique->getTypeReplique(),
    //             'puissance' => $replique->getPuissance(),
    //             'id'        => $replique->getIdreplique()
    //         ];
    //     }
    //     return new JsonModel([
    //         'data' => $data
    //     ]);
    // }
    

    // public function ajaxAddRepliqueAction()
    // {
    //     $currentUser = $this->authService->getIdentity();
    //     $request = $this->getRequest();
    //     if (!$request->isXmlHttpRequest() || !$request->isPost()) {
    //         return new JsonModel([
    //             'success' => false,
    //             'message' => 'Requête non autorisée'
    //         ]);
    //     }
    
    //     $form = new RepliqueForm;
    //     $data = $request->getPost()->toArray();
    
    //     if (isset($data['puissance'])) {
    //         $data['puissance'] = str_replace(',', '.', $data['puissance']);
    //     }
    
    //     $form->setData($data);
    
    //     if ($form->isValid()) {
    //         $replique = $this->repliqueManager->addReplique($form->getData(),$currentUser);
    
    //         return new JsonModel([
    //             'success' => true,
    //             'id' => $replique->getIdreplique(),
    //             'nom' => $replique->getNomReplique(),
    //             'type' => $replique->getTypeReplique(),
    //             'puissance' => $replique->getPuissance(),
    //         ]);
    //     }
    
    //     return new JsonModel([
    //         'success' => false,
    //         'errors' => $form->getMessages()
    //     ]);
    // }
    

    // public function ajaxDeleteRepliqueAction()
    // {
    //     $request = $this->getRequest();
    
    //     if (!$request->isXmlHttpRequest() || !$request->isPost()) {
    //         return new JsonModel([
    //             'success' => false,
    //             'message' => 'Requête non autorisée'
    //         ]);
    //     }
    
    //     $idreplique = $request->getPost('idreplique');
    //     if (empty($idreplique)) {
    //         return new JsonModel([
    //             'success' => false,
    //             'message' => 'ID manquant'
    //         ]);
    //     }
    //     $replique = $this->entityManager->getRepository(Replique::class)->findOneBy(['idreplique'=>$idreplique]);
    //     if ($replique) {
    //         $this->repliqueManager->deleteReplique($replique);
    //         return new JsonModel([
    //             'success' => true,
    //             'message' => 'Réplique supprimée'
    //         ]);
    //     }
    
    //     return new JsonModel([
    //         'success' => false,
    //         'message' => 'Réplique introuvable'
    //     ]);
    // }
    
}
