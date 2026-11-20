<?php
namespace Messagerie\Controller;

use Application\Service\AuthService;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Messagerie\Service\MessagerieManager;
use User\Entity\User;

/**
 * GOD MODE > Messagerie : message de l'administration a un membre, aux
 * membres de l'association ou a tous les comptes valides. Sans reponse possible.
 */
class MessagerieAdminController extends AbstractActionController
{
    private EntityManager $entityManager;
    private AuthService $authService;
    private MessagerieManager $manager;

    public function __construct(EntityManager $entityManager, AuthService $authService, MessagerieManager $manager)
    {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->manager = $manager;
    }

    public function sendAction()
    {
        $admin = $this->authService->getIdentity();
        if (!$admin instanceof User || !$admin->isGod()) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }

        $request = $this->getRequest();
        $values = ['audience' => MessagerieManager::AUDIENCE_ONE, 'user_id' => 0, 'subject' => '', 'body' => ''];
        if ($request->isPost()) {
            $values = [
                'audience' => (string) $request->getPost('audience'),
                'user_id' => InputSanitizer::cleanInt($request->getPost('user_id')),
                'subject' => InputSanitizer::cleanString($request->getPost('subject')),
                'body' => InputSanitizer::cleanText($request->getPost('body')),
            ];
            $recipients = $this->manager->audience($values['audience'], $values['user_id']);
            if (!$request->getPost('confirm')) {
                $this->flashMessenger()->addErrorMessage('Cochez la confirmation d’envoi (' . count($recipients) . ' destinataire(s)).');
            } else {
                $result = $this->manager->broadcast($admin, $recipients, $values['subject'], $request->getPost('body'));
                if (!$result['error']) {
                    $this->flashMessenger()->addSuccessMessage('Message envoyé à ' . $result['count'] . ' destinataire(s).');
                    return $this->redirect()->toRoute('admin-messagerie');
                }
                $this->flashMessenger()->addErrorMessage($result['error']);
            }
        }

        $counts = [];
        foreach ([MessagerieManager::AUDIENCE_MEMBERS, MessagerieManager::AUDIENCE_ALL] as $audience) {
            $counts[$audience] = count($this->manager->audience($audience));
        }
        $users = $this->manager->audience(MessagerieManager::AUDIENCE_ALL);
        usort($users, fn (User $a, User $b) => strcasecmp($this->label($a), $this->label($b)));

        $this->layout()->setVariable('activeMenu', 'admin-messagerie');
        $view = new ViewModel([
            'values' => $values,
            'counts' => $counts,
            'users' => array_map(fn (User $user) => ['id' => $user->getIdUser(), 'label' => $this->label($user)], $users),
            'batches' => $this->manager->conversations()->adminBatches(),
        ]);
        $view->setTemplate('messagerie-admin/send');
        return $view;
    }

    private function label(User $user): string
    {
        $name = trim($user->getFirstname() . ' ' . $user->getLastname());
        $nickname = trim((string) $user->getNickname());
        return $nickname !== '' ? $nickname . ' (' . $name . ')' : $name;
    }
}
