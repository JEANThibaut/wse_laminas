<?php

namespace Game\Controller;

use Application\Util\InputSanitizer;
use Application\Service\SumUpService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Game\Entity\Game;
use Game\Entity\GameRegister;
use Game\Entity\PaymentTransaction;
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

        $id = InputSanitizer::cleanInt($this->params()->fromQuery('id'));
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame'=>$id]);
        if (!$game) {
            $this->flashMessenger()->addErrorMessage('Partie introuvable.');
            return $this->redirect()->toRoute('home');
        }

        $user = $this->entityManager->getRepository(User::class)->find($currentUser->getIdUser());
        if (! $user) {
            $this->flashMessenger()->addErrorMessage('Utilisateur introuvable.');
            return $this->redirect()->toRoute('home');
        }

        $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
            'user' => $user,
            'game' => $game,
            'status' => GameRegister::STATUS_ACTIVE,
        ]);

        if ($register) {
            $this->flashMessenger()->addSuccessMessage('Vous êtes déjà inscrit à cette partie.');
            return $this->redirect()->toRoute('home');
        }

        // Ancien flux de paiement en ligne temporairement mis de cote.
        $registered = $this->gameManager->registerInGame($game, $currentUser);
        if (! $registered) {
            $this->flashMessenger()->addErrorMessage('L\'inscription n\'a pas pu être finalisée.');
            return $this->redirect()->toRoute('home');
        }

        $this->flashMessenger()->addSuccessMessage('Votre inscription est enregistrée.');
        return $this->redirect()->toRoute('home');

        if (! $this->sumupService->hasValidConfiguration()) {
            $this->flashMessenger()->addErrorMessage('Le paiement n\'est pas disponible pour le moment.');
            return $this->redirect()->toRoute('home');
        }

        $eventConfig = $this->sumupConfig['event_registration'] ?? [];
        $amount = $this->resolveRegistrationAmount((bool)$user->getIsMember());
        $currency = strtoupper((string)($eventConfig['currency'] ?? 'EUR'));

        if ($amount <= 0) {
            $this->flashMessenger()->addErrorMessage('Le montant de l\'inscription est indisponible pour le moment.');
            return $this->redirect()->toRoute('home');
        }

        $checkoutReference = sprintf('game-%d-user-%d-%d', $game->getIdgame(), $user->getIdUser(), time());
        $redirectUrl = $this->buildPaymentReturnUrl($game->getIdgame(), $checkoutReference);
        $returnUrl = $redirectUrl;

        $checkout = $this->sumupService->createCheckout(
            $amount,
            $currency,
            $checkoutReference,
            'Inscription à la partie #' . $game->getIdgame(),
            $returnUrl,
            $redirectUrl
        );

        if (! is_array($checkout)) {
            $this->flashMessenger()->addErrorMessage('Impossible d\'ouvrir le paiement en ligne pour le moment.');
            return $this->redirect()->toRoute('home');
        }

        $hostedCheckoutUrl = trim((string)($checkout['hosted_checkout_url'] ?? ''));
        $nextStepUrl = '';
        if (isset($checkout['next_step']['url']) && is_string($checkout['next_step']['url'])) {
            $nextStepUrl = trim($checkout['next_step']['url']);
        }

        // SumUp may return next_step URL asynchronously; refresh checkout before redirecting.
        if ($nextStepUrl === '' && $checkoutReference !== '') {
            for ($attempt = 0; $attempt < 2 && $nextStepUrl === ''; $attempt++) {
                usleep(200000);
                $checkoutRefreshed = $this->sumupService->getCheckoutByReference($checkoutReference);
                if (! is_array($checkoutRefreshed)) {
                    continue;
                }

                if ($hostedCheckoutUrl === '' && isset($checkoutRefreshed['hosted_checkout_url']) && is_string($checkoutRefreshed['hosted_checkout_url'])) {
                    $hostedCheckoutUrl = trim($checkoutRefreshed['hosted_checkout_url']);
                }

                if (isset($checkoutRefreshed['next_step']['url']) && is_string($checkoutRefreshed['next_step']['url'])) {
                    $nextStepUrl = trim($checkoutRefreshed['next_step']['url']);
                }
            }
        }

        // Prefer full-page redirect flow first to avoid 3DS iframe/popup issues.
        $paymentUrl = $nextStepUrl !== '' ? $nextStepUrl : '';

        if (
            $paymentUrl === ''
            && isset($checkout['redirect_url'])
            && is_string($checkout['redirect_url'])
            && stripos($checkout['redirect_url'], 'sumup.com') !== false
        ) {
            $paymentUrl = trim($checkout['redirect_url']);
        }

        if ($paymentUrl === '') {
            $paymentUrl = $hostedCheckoutUrl;
        }

        if ($paymentUrl === '' && isset($checkout['id']) && is_string($checkout['id'])) {
            $checkoutIdForUrl = trim($checkout['id']);
            if ($checkoutIdForUrl !== '') {
                $paymentUrl = 'https://checkout.sumup.com/pay/c-' . rawurlencode($checkoutIdForUrl);
            }
        }

        if ($paymentUrl === '') {
            $this->flashMessenger()->addErrorMessage('Le paiement n\'a pas pu être ouvert. Réessayez dans quelques instants.');
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

        $gameId = InputSanitizer::cleanInt($this->params()->fromQuery('game', 0));
        $checkoutReference = InputSanitizer::cleanString($this->params()->fromQuery('reference', ''));
        $checkoutId = InputSanitizer::cleanString($this->params()->fromQuery('checkout_id', ''));

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
        if ($existingRegister && $this->isRegisterPaid($existingRegister)) {
            $this->flashMessenger()->addSuccessMessage('Votre inscription est confirmée.');
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
                    $this->flashMessenger()->addErrorMessage('Le paiement a bien été confirmé, mais l\'inscription n\'a pas pu être finalisée.');
                    return $this->redirect()->toRoute('home');
                }
                $register = $this->findUserGameRegister($currentUser->getIdUser(), $gameId);
                if ($register) {
                    $this->savePaidTransaction($register, $checkout);
                }
                $this->flashMessenger()->addSuccessMessage('Votre inscription est confirmée.');
                return $this->redirect()->toRoute('home');
            }

            $this->flashMessenger()->addSuccessMessage('Paiement reçu. Votre inscription sera confirmée dans quelques instants.');
            return $this->redirect()->toRoute('home');
        }

        $this->flashMessenger()->addErrorMessage('Le paiement n\'a pas été confirmé (statut: ' . $status . ').');
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
            $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
                'idregister' => $registerId,
                'status' => GameRegister::STATUS_ACTIVE,
            ]);
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

        $register = $this->findUserGameRegister($parsedReference['userId'], $parsedReference['gameId']);
        if ($register) {
            $this->savePaidTransaction($register, $checkout);
        }

        return $this->emptyResponse(204);
    }

    
    
    public function unregisterInGameAction(){
  
        $request = $this->getRequest();
        if ($request->isPost()) {
            $currentUser = $this->authService->getIdentity();
            if (! $currentUser) {
                $this->flashMessenger()->addErrorMessage('Vous devez être connecté pour vous désinscrire.');
                return $this->redirect()->toRoute('login');
            }
            $id = InputSanitizer::cleanInt($this->params()->fromPost('id'));
            $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
                'idregister' => $id,
                'status' => GameRegister::STATUS_ACTIVE,
            ]);
            if($register){
                $registerUser = $register->getUser();
                if (! $registerUser || (int)$registerUser->getIdUser() !== (int)$currentUser->getIdUser()) {
                    $this->flashMessenger()->addErrorMessage('Action non autorisée.');
                    return $this->redirect()->toRoute('home');
                }

                $paymentTransaction = $this->getRefundableTransactionForRegister($register);
                $manualRefundMessage = null;
                if ($paymentTransaction) {
                    $isRefundEligible = $this->isRefundEligible($register);
                    if ($isRefundEligible) {
                        if (! $paymentTransaction->getProviderTransactionId()) {
                            $this->flashMessenger()->addErrorMessage('Le remboursement n\'a pas pu être lancé car la transaction de paiement est introuvable.');
                            return $this->redirect()->toRoute('home');
                        }

                        $refundAmount = $this->resolveRefundAmount((int)$registerUser->getIdUser());
                        $refundResult = $this->sumupService->refundTransactionDetailed(
                            $paymentTransaction->getProviderTransactionId(),
                            $refundAmount
                        );
                        if (! ($refundResult['success'] ?? false)) {
                            $paymentTransaction->setStatus('refund_pending_manual');
                            $this->entityManager->flush();
                            $manualRefundMessage = 'Désinscription enregistrée. Le remboursement sera traité sous 24 à 48h.';
                        } else {
                            $paymentTransaction->setStatus('refunded');
                            $paymentTransaction->setRefundedAmount($refundAmount);
                            $paymentTransaction->setRefundDoneAt(new \DateTime());
                            $this->entityManager->flush();
                        }
                    } else {
                        $this->flashMessenger()->addWarningMessage('Désinscription enregistrée. Aucun remboursement n\'est prévu à moins de 24h de la partie.');
                    }
                } elseif ((int)$register->getPaid() === 1) {
                    // Inscription sans transaction SumUp: aucune action de remboursement automatique a effectuer.
                }

                $register->setStatus(GameRegister::STATUS_CANCELLED);
                $register->setArrivedNumber(0);
                $this->entityManager->flush();
                if ($manualRefundMessage !== null) {
                    $this->flashMessenger()->addWarningMessage($manualRefundMessage);
                }
                $this->flashMessenger()->addSuccessMessage('Désinscription réussie.');
            }
            else{
                $this->flashMessenger()->addErrorMessage('Une erreur est survenue pendant la désinscription.');
            }
        }
            

        return $this->redirect()->toRoute('home');
    }


    public function registerInWaitingListAction(){
        
        $currentUser = $this->authService->getIdentity();
        $id = InputSanitizer::cleanInt($this->params()->fromQuery('id'));
        $waitingList  = $this->entityManager->getRepository(WaitingList::class)->findBy(['game_id'=>$id]);
         $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame'=>$id]);
        $count = count($waitingList);
        $newWaitingList = $this->gameManager->registerInWaitingList($currentUser,$game, $count);

       
      return $this->redirect()->toRoute('home');
    }


    
    public function unregisterInWaitingListAction(){
  
        $currentUser = $this->authService->getIdentity();
        $id = InputSanitizer::cleanInt($this->params()->fromQuery('id'));
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

    private function isMobileClient(): bool
    {
        $userAgent = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($userAgent === '') {
            return false;
        }

        return (bool) preg_match('/android|iphone|ipad|ipod|mobile|webview|wv|opera mini/i', $userAgent);
    }

    private function buildPaymentReturnUrl(int $gameId, string $checkoutReference): string
    {
        $query = [
            'game' => $gameId,
            'reference' => $checkoutReference,
        ];

        $pathWithQuery = $this->url()->fromRoute(
            'register-in-game-payment-return',
            [],
            [
                'query' => $query,
            ]
        );

        $publicBaseUrl = trim((string)($this->sumupConfig['public_base_url'] ?? ''));
        if ($publicBaseUrl !== '') {
            return rtrim($publicBaseUrl, '/') . $pathWithQuery;
        }

        return $this->url()->fromRoute(
            'register-in-game-payment-return',
            [],
            [
                'force_canonical' => true,
                'query' => $query,
            ]
        );
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

    private function findUserGameRegister(int $userId, int $gameId): ?GameRegister
    {
        if ($userId <= 0 || $gameId <= 0) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $game = $this->entityManager->getRepository(Game::class)->findOneBy(['idgame' => $gameId]);
        if (! $user || ! $game) {
            return null;
        }

        $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
            'user' => $user,
            'game' => $game,
            'status' => GameRegister::STATUS_ACTIVE,
        ]);

        return $register instanceof GameRegister ? $register : null;
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

        $register = $this->entityManager->getRepository(GameRegister::class)->findOneBy([
            'user' => $user,
            'game' => $game,
            'status' => GameRegister::STATUS_ACTIVE,
        ]);

        if (! $register) {
            $register = new GameRegister();
            $register->setUser($user);
            $register->setGame($game);
            $register->setArrivedNumber(0);
            $register->setMember((int)$user->getIsMember());
            $register->setStatus(GameRegister::STATUS_ACTIVE);
            $this->entityManager->persist($register);
        }

        $register->setPaid(1);
        $this->entityManager->flush();

        return true;
    }

    private function savePaidTransaction(GameRegister $register, array $checkout): void
    {
        $transactionId = $this->extractCheckoutTransactionId($checkout);
        if ($transactionId === '') {
            return;
        }

        $existingPaidForRegister = $this->getRefundableTransactionForRegister($register);
        if ($existingPaidForRegister) {
            return;
        }

        $existing = $this->entityManager->getRepository(PaymentTransaction::class)->findOneBy([
            'register' => $register,
            'provider_transaction_id' => $transactionId,
        ]);

        if ($existing) {
            if ($existing->getStatus() !== 'paid') {
                $existing->setStatus('paid');
                $this->entityManager->flush();
            }
            return;
        }

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setRegister($register);
        $paymentTransaction->setProviderTransactionId($transactionId);
        $paymentTransaction->setStatus('paid');
        $this->entityManager->persist($paymentTransaction);
        $this->entityManager->flush();
    }

    private function getRefundableTransactionForRegister(GameRegister $register): ?PaymentTransaction
    {
        $transaction = $this->entityManager->getRepository(PaymentTransaction::class)->findOneBy([
            'register' => $register,
            'status' => 'paid',
            'refund_done_at' => null,
        ]);

        return $transaction instanceof PaymentTransaction ? $transaction : null;
    }

    private function isRegisterPaid(GameRegister $register): bool
    {
        if ($this->getRefundableTransactionForRegister($register) !== null) {
            return true;
        }

        // Compatibility for old registrations created before transaction tracking.
        return (int)$register->getPaid() === 1;
    }

    private function extractCheckoutTransactionId(array $checkout): string
    {
        if (isset($checkout['transaction_id']) && is_string($checkout['transaction_id']) && trim($checkout['transaction_id']) !== '') {
            return trim($checkout['transaction_id']);
        }

        if (isset($checkout['transactions']) && is_array($checkout['transactions']) && isset($checkout['transactions'][0]) && is_array($checkout['transactions'][0])) {
            $candidate = $checkout['transactions'][0]['id'] ?? '';
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function resolveRegistrationAmount(bool $isMember): float
    {
        $eventConfig = $this->sumupConfig['event_registration'] ?? [];

        return array_key_exists('amount', $eventConfig)
            ? (float)$eventConfig['amount']
            : ($isMember
                ? (float)($eventConfig['amount_member'] ?? 0)
                : (float)($eventConfig['amount_non_member'] ?? 0));
    }

    private function resolveRefundAmount(int $userId): float
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $isMember = $user ? (bool)$user->getIsMember() : false;

        return max(0, $this->resolveRegistrationAmount($isMember) - 0.50);
    }

    private function isRefundEligible(GameRegister $register): bool
    {
        $game = $register->getGame();
        if (! $game || ! method_exists($game, 'getDate')) {
            return false;
        }

        $gameDate = $game->getDate();
        if (! $gameDate instanceof \DateTimeInterface) {
            return false;
        }

        $limit = (new \DateTimeImmutable($gameDate->format('Y-m-d H:i:s')))->modify('-24 hours');
        return new \DateTimeImmutable('now') <= $limit;
    }

  
}
