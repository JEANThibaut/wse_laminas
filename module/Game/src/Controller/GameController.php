<?php

namespace Game\Controller;

use Application\Service\SumUpService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\Register;
use Game\Entity\WaitingList;
use Game\Form\GameForm;
use User\Entity\User;

class GameController extends AbstractActionController
{

    private $authService;
    private $entityManager;
    private $gameManager;
    private SumUpService $sumupService;
    private array $sumupConfig;


    public function __construct($entityManager, $authService, $gameManager, SumUpService $sumupService, array $sumupConfig)
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
        $this->gameManager = $gameManager;
        $this->sumupService = $sumupService;
        $this->sumupConfig = $sumupConfig;

    }


    public function registerInGameAction(){
 
        $currentUser = $this->authService->getIdentity();
        if (! $currentUser) {
            $this->flashMessenger()->addErrorMessage('Vous devez être connecté pour vous inscrire.');
            return $this->redirect()->toRoute('login');
        }

        $id = (int) $this->params()->fromQuery('id'); 
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame'=>$id]);
        if (!$game) {
            $this->flashMessenger()->addErrorMessage('Pas de partie trouvée');
            return $this->redirect()->toRoute('home');
        }

        $user = $this->entityManager->getRepository(User::class)->find($currentUser->getIdUser());
        if (! $user) {
            $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
            return $this->redirect()->toRoute('home');
        }

        $register = $this->entityManager->getRepository(Register::class)->findOneBy([
            'user' => $user,
            'game' => $game,
        ]);

        if ($register && $register->getPaid() === 1) {
            $this->flashMessenger()->addSuccessMessage('Vous êtes déjà inscrit et votre paiement est validé.');
            return $this->redirect()->toRoute('home');
        }

        if (! $this->sumupService->hasValidConfiguration()) {
            $this->flashMessenger()->addSuccessMessage('Votre inscription à bien été prise en compte');
            return $this->redirect()->toRoute('home');
        }

        $eventConfig = $this->sumupConfig['event_registration'] ?? [];
        $amount = array_key_exists('amount', $eventConfig)
            ? (float)$eventConfig['amount']
            : ($user->getIsMember()
                ? (float)($eventConfig['amount_member'] ?? 0)
                : (float)($eventConfig['amount_non_member'] ?? 0));
        $currency = strtoupper((string)($eventConfig['currency'] ?? 'EUR'));

        if ($amount <= 0) {
            $this->ensurePaidRegistration($game->getIdgame(), $user->getIdUser());
            $this->flashMessenger()->addSuccessMessage('Votre inscription a bien été validée.');
            return $this->redirect()->toRoute('home');
        }

        $checkoutReference = sprintf('game-%d-user-%d-%d', $game->getIdgame(), $user->getIdUser(), time());
        $redirectUrl = $this->url()->fromRoute(
            'register-in-game-payment-return',
            [],
            [
                'force_canonical' => true,
                'query' => [
                    'game' => $game->getIdgame(),
                    'reference' => $checkoutReference,
                ],
            ]
        );
        $returnUrl = $this->sumupService->isWebhookEnabled()
            ? $this->url()->fromRoute(
                'sumup-webhook',
                [],
                [
                    'force_canonical' => true,
                ]
            )
            : $redirectUrl;

        $checkout = $this->sumupService->createCheckout(
            $amount,
            $currency,
            $checkoutReference,
            'Inscription à la partie #' . $game->getIdgame(),
            $returnUrl,
            $redirectUrl
        );

        if (! is_array($checkout)) {
            $this->flashMessenger()->addErrorMessage('Impossible de lancer le paiement en ligne pour le moment.');
            return $this->redirect()->toRoute('home');
        }

        $paymentUrl = trim((string)($checkout['hosted_checkout_url'] ?? ''));
        if ($paymentUrl === '' && isset($checkout['next_step']['url']) && is_string($checkout['next_step']['url'])) {
            $paymentUrl = trim($checkout['next_step']['url']);
        }

        if (
            $paymentUrl === ''
            && isset($checkout['redirect_url'])
            && is_string($checkout['redirect_url'])
            && stripos($checkout['redirect_url'], 'sumup.com') !== false
        ) {
            $paymentUrl = trim($checkout['redirect_url']);
        }

        if ($paymentUrl === '') {
            $this->flashMessenger()->addErrorMessage('Paiement créé mais URL de paiement manquante.');
            return $this->redirect()->toRoute('home');
        }

      return $this->redirect()->toUrl($paymentUrl);
    }

    public function registerInGamePaymentReturnAction()
    {
        $currentUser = $this->authService->getIdentity();
        if (! $currentUser) {
            $this->flashMessenger()->addErrorMessage('Vous devez être connecté pour valider le paiement.');
            return $this->redirect()->toRoute('login');
        }

        $gameId = (int)$this->params()->fromQuery('game', 0);
        $checkoutReference = (string)$this->params()->fromQuery('reference', '');
        $checkoutId = (string)$this->params()->fromQuery('checkout_id', '');

        if (($gameId <= 0 && $checkoutReference === '') || ($checkoutReference === '' && $checkoutId === '')) {
            $this->flashMessenger()->addErrorMessage('Retour de paiement invalide.');
            return $this->redirect()->toRoute('home');
        }

        $parsedReference = $this->extractGameUserFromReference($checkoutReference);
        if ($parsedReference !== null) {
            if ($gameId === 0) {
                $gameId = $parsedReference['gameId'];
            }
            if ($parsedReference['userId'] !== $currentUser->getIdUser()) {
                $this->flashMessenger()->addErrorMessage('Ce paiement ne correspond pas à votre compte.');
                return $this->redirect()->toRoute('home');
            }
        }

        $checkout = $this->resolveCheckout($checkoutReference, $checkoutId);
        if (! is_array($checkout)) {
            $this->flashMessenger()->addErrorMessage('Impossible de vérifier le paiement auprès de SumUp.');
            return $this->redirect()->toRoute('home');
        }

        $existingRegister = $this->findUserGameRegister($currentUser->getIdUser(), $gameId);
        if ($existingRegister && $existingRegister->getPaid() === 1) {
            $this->flashMessenger()->addSuccessMessage('Paiement confirmé par le serveur, votre inscription est validée.');
            return $this->redirect()->toRoute('home');
        }

        $status = strtoupper((string)($checkout['status'] ?? 'PENDING'));
        if ($status === 'PENDING' && $this->sumupService->isReturnConfirmationEnabled()) {
            for ($attempt = 0; $attempt < 5 && $status === 'PENDING'; $attempt++) {
                usleep(800000);
                $checkoutRetry = $this->resolveCheckout($checkoutReference, $checkoutId);
                if (is_array($checkoutRetry)) {
                    $checkout = $checkoutRetry;
                    $status = strtoupper((string)($checkout['status'] ?? 'PENDING'));
                }
            }
        }

        if ($status === 'PAID') {
            if ($this->sumupService->isReturnConfirmationEnabled()) {
                $registered = $this->ensurePaidRegistration($gameId, $currentUser->getIdUser());
                if (! $registered) {
                    $this->flashMessenger()->addErrorMessage('Paiement OK mais inscription introuvable.');
                    return $this->redirect()->toRoute('home');
                }
                $this->flashMessenger()->addSuccessMessage('Paiement confirmé (mode test local), votre inscription est validée.');
                return $this->redirect()->toRoute('home');
            }

            $this->flashMessenger()->addSuccessMessage('Paiement reçu, confirmation serveur en cours (webhook). Actualisez dans quelques secondes.');
            return $this->redirect()->toRoute('home');
        }

        $this->flashMessenger()->addErrorMessage('Paiement non confirmé (statut: ' . $status . ').');
        return $this->redirect()->toRoute('home');
    }

    public function sumupWebhookAction()
    {
        $request = $this->getRequest();

        if (! $request->isPost()) {
            return $this->emptyResponse(405);
        }

        if (! $this->sumupService->hasValidConfiguration() || ! $this->sumupService->isWebhookEnabled()) {
            return $this->emptyResponse(503);
        }

        $rawBody = (string)$request->getContent();
        if ($rawBody === '') {
            return $this->emptyResponse(204);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return $this->emptyResponse(204);
        }

        $eventType = strtoupper($this->findPayloadString($payload, [
            ['event_type'],
            ['eventType'],
            ['data', 'event_type'],
        ]));
        if ($eventType !== '' && $eventType !== 'CHECKOUT_STATUS_CHANGED') {
            return $this->emptyResponse(204);
        }

        $checkoutReference = $this->findPayloadString($payload, [
            ['checkout_reference'],
            ['checkoutReference'],
            ['data', 'checkout_reference'],
            ['payload', 'checkout_reference'],
            ['checkout', 'checkout_reference'],
        ]);

        $checkoutId = $this->findPayloadString($payload, [
            ['id'],
            ['checkout_id'],
            ['checkoutId'],
            ['data', 'id'],
            ['payload', 'id'],
            ['checkout', 'id'],
        ]);

        $checkout = null;
        if ($checkoutReference !== '') {
            $checkout = $this->sumupService->getCheckoutByReference($checkoutReference);
        } elseif ($checkoutId !== '') {
            $checkout = $this->sumupService->getCheckoutById($checkoutId);
        }

        if (! is_array($checkout)) {
            return $this->emptyResponse(204);
        }

        $status = strtoupper((string)($checkout['status'] ?? 'PENDING'));
        if ($status !== 'PAID') {
            return $this->emptyResponse(204);
        }

        $resolvedReference = (string)($checkout['checkout_reference'] ?? $checkoutReference);

        if (preg_match('/^game-\d+-register-(\d+)-\d+$/', $resolvedReference, $legacyMatches)) {
            $registerId = (int)$legacyMatches[1];
            $register = $this->entityManager->getRepository(Register::class)->findOneBy(['idregister' => $registerId]);
            if (! $register) {
                return $this->emptyResponse(204);
            }

            if ($register->getPaid() === 0) {
                $register->setPaid(1);
                $this->entityManager->flush();
            }

            return $this->emptyResponse(204);
        }

        $parsedReference = $this->extractGameUserFromReference($resolvedReference);
        if ($parsedReference === null) {
            return $this->emptyResponse(204);
        }

        $registered = $this->ensurePaidRegistration($parsedReference['gameId'], $parsedReference['userId']);
        if (! $registered) {
            return $this->emptyResponse(204);
        }

        return $this->emptyResponse(204);
    }

    
    
    public function unregisterInGameAction(){
  
        $request = $this->getRequest();
        if ($request->isPost()) {
            $currentUser = $this->authService->getIdentity();
            $id = $this->params()->fromPost('id');
            $register = $this->entityManager->getRepository(Register::class)->findOneBy(['idregister'=>$id]);
            if($register){
                $this->entityManager->remove($register);
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('Désinscription réussie.');
            }
            else{
                $this->flashMessenger()->addSuccessMessage('Une erreur est survenue.');
            }
        }
            

        return $this->redirect()->toRoute('home');
    }


    public function registerInWaitingListAction(){
        
        $currentUser = $this->authService->getIdentity();
        $id = (int) $this->params()->fromQuery('id'); 
        $waitingList  = $this->entityManager->getRepository(WaitingList::class)->findBy(['game_id'=>$id]);
         $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame'=>$id]);
        $count = count($waitingList);
        $newWaitingList = $this->gameManager->registerInWaitingList($currentUser,$game, $count);

       
      return $this->redirect()->toRoute('home');
    }


    
    public function unregisterInWaitingListAction(){
  
        $currentUser = $this->authService->getIdentity();
        $id = (int) $this->params()->fromQuery('id'); 
        $waitingList  = $this->entityManager->getRepository(WaitingList::class)->findOneBy(['game_id'=>$id, 'user_id'=>$currentUser->getIdUser()]);
  
        if($waitingList != null){
            $this->entityManager->remove($waitingList);
            $this->entityManager->flush();
        }
        // $count = count($waitingList);
        // $newWaitingList = $this->gameManager->registerInWaitingList($currentUser,$game, $count);

       
      return $this->redirect()->toRoute('home');
    }

    private function emptyResponse(int $statusCode)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->setContent('');

        return $response;
    }

    private function findPayloadString(array $payload, array $paths): string
    {
        foreach ($paths as $path) {
            $value = $payload;

            foreach ($path as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    continue 2;
                }

                $value = $value[$segment];
            }

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private function resolveCheckout(string $checkoutReference, string $checkoutId): ?array
    {
        if ($checkoutId !== '') {
            $checkout = $this->sumupService->getCheckoutById($checkoutId);
            if (is_array($checkout)) {
                return $checkout;
            }
        }

        if ($checkoutReference !== '') {
            $checkout = $this->sumupService->getCheckoutByReference($checkoutReference);
            if (is_array($checkout)) {
                return $checkout;
            }
        }

        return null;
    }

    private function extractGameUserFromReference(string $checkoutReference): ?array
    {
        if (! preg_match('/^game-(\d+)-user-(\d+)-\d+$/', $checkoutReference, $matches)) {
            return null;
        }

        return [
            'gameId' => (int)$matches[1],
            'userId' => (int)$matches[2],
        ];
    }

    private function findUserGameRegister(int $userId, int $gameId): ?Register
    {
        if ($userId <= 0 || $gameId <= 0) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame' => $gameId]);
        if (! $user || ! $game) {
            return null;
        }

        $register = $this->entityManager->getRepository(Register::class)->findOneBy([
            'user' => $user,
            'game' => $game,
        ]);

        return $register instanceof Register ? $register : null;
    }

    private function ensurePaidRegistration(int $gameId, int $userId): bool
    {
        if ($gameId <= 0 || $userId <= 0) {
            return false;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame' => $gameId]);
        if (! $user || ! $game) {
            return false;
        }

        $register = $this->entityManager->getRepository(Register::class)->findOneBy([
            'user' => $user,
            'game' => $game,
        ]);

        if (! $register) {
            $register = new Register();
            $register->setUser($user);
            $register->setGame($game);
            $register->setArrivedNumber(0);
            $register->setMember((int)$user->getIsMember());
            $this->entityManager->persist($register);
        }

        $register->setPaid(1);
        $this->entityManager->flush();

        return true;
    }

  
}
