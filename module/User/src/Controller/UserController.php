<?php
namespace User\Controller;

use Application\Service\AuthService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\UserManager;
use User\Entity\User;
use User\Repository\UserRepository;
use Game\Entity\GameRegister;
use Application\Util\InputSanitizer;

class UserController extends AbstractActionController
{   
    private $entityManager;
    private $userManager;
    private $authService;
    public function __construct($entityManager,  UserManager $userManager, AuthService $authService)
    {
        $this->entityManager = $entityManager;
        $this->userManager = $userManager;
        $this->authService = $authService;
    }




    public function usersAction(){
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $currentUser = $this->authService->getIdentity();
        $userRepository = $this->entityManager->getRepository(User::class);
        $view = new ViewModel([
            'currentUser' => $currentUser,
            'users' => $userRepository->search('', UserRepository::SCOPE_PLAYERS),
            'members' => $userRepository->search('', UserRepository::SCOPE_MEMBERS),
            'inactiveUsers' => $userRepository->search('', UserRepository::SCOPE_INACTIVE),
            'unvalidatedToDeactivate' => $userRepository->findUnvalidatedToDeactivate(),
            'unvalidatedActive' => $userRepository->findUnvalidatedActive(),
            'stats' => $this->getParticipationStats(),
        ]);
        $this->layout()->setVariable('activeMenu', 'admin-users');
        $view->setTemplate('admin/users');
        // $view->setTerminal(true); 
        return $view;
    }

    /**
     * Recherche AJAX de la liste admin : renvoie uniquement les lignes du tableau.
     * ?status= players (defaut), members ou inactive : la liste a filtrer.
     */
    public function searchUsersAction()
    {
        if ($this->authService->requireRoles(['admin'], $this->redirect())) {
            return $this->getResponse()->setStatusCode(403);
        }
        $term = InputSanitizer::cleanString($this->params()->fromQuery('q'));
        $scope = $this->params()->fromQuery('status');
        if (!in_array($scope, [UserRepository::SCOPE_MEMBERS, UserRepository::SCOPE_INACTIVE], true)) {
            $scope = UserRepository::SCOPE_PLAYERS;
        }
        $users = $this->entityManager->getRepository(User::class)->search($term, $scope);

        $view = new ViewModel([
            'users' => $users,
            'stats' => $this->getParticipationStats(),
        ]);
        $view->setTemplate('admin/partial/user-rows');
        $view->setTerminal(true);
        return $view;
    }

    /**
     * Desactive tous les comptes actifs sans email valide, hors membres,
     * admins et GOD.
     */
    public function deactivateUnvalidatedAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin-users');
        }

        $users = $this->entityManager->getRepository(User::class)->findUnvalidatedToDeactivate();
        foreach ($users as $user) {
            $user->setIsActive(false);
        }
        $this->entityManager->flush();

        $this->flashMessenger()->addSuccessMessage(count($users) . ' compte(s) sans email validé désactivé(s).');
        return $this->redirect()->toRoute('admin-users');
    }

    /**
     * Envoie le lien de validation a tous les comptes actifs dont l'email
     * n'est pas encore valide.
     */
    public function sendValidationEmailsAction()
    {
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin-users');
        }

        $users = $this->entityManager->getRepository(User::class)->findUnvalidatedActive();
        $result = $this->authService->sendEmailValidationLinks(
            $users,
            fn (array $params) => $this->url()->fromRoute('validate-email', [], ['force_canonical' => true, 'query' => $params])
        );

        $this->flashMessenger()->addSuccessMessage($result['sent'] . ' mail(s) de validation envoyé(s).');
        if ($result['failed']) {
            $this->flashMessenger()->addErrorMessage(
                count($result['failed']) . ' envoi(s) en échec : ' . implode(', ', $result['failed'])
            );
        }
        return $this->redirect()->toRoute('admin-users');
    }

    private function getParticipationStats(): array
    {
        return $this->entityManager->getRepository(GameRegister::class)->getParticipationStatsByUser();
    }

     public function editUserAction(){
            if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
                $this->flashMessenger()->addErrorMessage('Accès refusé.');
                return $redirect;
            }      
        $currentUser = $this->authService->getIdentity();
        $request = $this->getRequest();
        $iduser = InputSanitizer::cleanInt($this->params()->fromRoute('iduser'));
        if($request->isPost()){
            $data = InputSanitizer::cleanArray($request->getPost()->toArray());
            // dump($data);
            if(!empty($data['first_name']) && !empty($data['last_name']) && !empty($data['email']) ){
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
                if($user && strcasecmp(trim($data['email']), trim((string) $user->getEmail())) !== 0
                    && $this->authService->isEmailDomainBlocked($data['email'])) {
                    // Adresse jetable refusee, seulement si elle change
                    $this->flashMessenger()->addErrorMessage(AuthService::BLOCKED_EMAIL_MESSAGE);
                    return $this->redirect()->toRoute('admin-edit-user', ['iduser' => $iduser]);
                }
                if($user){
                   
                    $user->setFirstName($data['first_name']);
                    $user->setLastName($data['last_name']);
                    $user->setEmail($data['email']);
                    // Super-admin : attribue par le GOD seulement. Traite avant Admin,
                    // pour que retirer les deux d'un coup retire bien l'admin
                    // (un super-admin est forcement admin)
                    if ($currentUser && $currentUser->isGod() && isset($data['isSuperAdmin'])) {
                        $user->setSuperAdmin(InputSanitizer::cleanBool($data['isSuperAdmin']));
                    }
                    $user->setIsAdmin(InputSanitizer::cleanBool($data['isAdmin'] ?? 0));
                    $user->setIsMember(InputSanitizer::cleanBool($data['isMember'] ?? 0));
                    $user->setIsBlacklist(InputSanitizer::cleanBool($data['isBlacklist'] ?? 0));
                    $this->entityManager->flush();
                    $this->flashMessenger()->addSuccessMessage('Utilisateur modifié avec succès.');
                    return $this->redirect()->toRoute('admin-users');
                }else{
                    $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
                }
            }else{
                $this->flashMessenger()->addErrorMessage('Veuillez remplir tous les champs.');
            }
        }       
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
        $view = new ViewModel([
            'currentUser'=>$currentUser,
            'user'=>$user
        ]);
        
        $this->layout()->setVariable('activeMenu', 'admin-users');
        $view->setTemplate('admin/edit-user');
        return $view;
    }
  
    public function deleteUserAction(){      

        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }  
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-users');
        }   
        $iduser = InputSanitizer::cleanInt($this->params()->fromPost('iduser'));

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
        if($user){
            $user->setIsActive(0);
            $this->entityManager->flush();
            $this->flashMessenger()->addSuccessMessage('Utilisateur supprimé avec succès.');
        }else{
            $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
        }
        return $this->redirect()->toRoute('admin-users');
    }

    public function sendResetPasswordAction(){

        $currentUser = $this->authService->getIdentity();
        if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $redirect;
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-users');
        }
        $iduser = InputSanitizer::cleanInt($this->params()->fromPost('iduser'));

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
        if($user){
            $sent = $this->authService->sendPasswordResetLink($user->getEmail());
            if ($sent) {
                $this->flashMessenger()->addSuccessMessage('Email de réinitialisation envoyé avec succès.');
                if ($currentUser) {
                    $this->authService->sendAdminResetNotification($currentUser->getEmail(), $user);
                }
            } else {
                $this->flashMessenger()->addErrorMessage("Échec de l'envoi de l'email de réinitialisation.");
            }
        }else{
            $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
        }
        return $this->redirect()->toRoute('admin-edit-user', ['iduser' => $iduser]);
    }

    public function generatePasswordAction(){

        $currentUser = $this->authService->getIdentity();
        if (!$currentUser || !$currentUser->isGod()) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('admin-users');
        }
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->redirect()->toRoute('admin-users');
        }
        $iduser = InputSanitizer::cleanInt($this->params()->fromPost('iduser'));

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['iduser' => $iduser]);
        if($user){
            $plainPassword = $this->authService->generateNewPassword($user);
            $this->flashMessenger()->addSuccessMessage('Nouveau mot de passe généré avec succès.');
            $this->flashMessenger()->setNamespace('generatedPassword')->addMessage($plainPassword);
        }else{
            $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
        }
        return $this->redirect()->toRoute('admin-edit-user', ['iduser' => $iduser]);
    }
}
