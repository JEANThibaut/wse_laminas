<?php

declare(strict_types=1);

namespace Parties\Controller;

use DateTime;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use Parties\Entity\Participants;
use Parties\Entity\Parties;
use Users\Entity\Users;

use function PHPSTORM_META\type;

// use Users\Entity\Users;

class ParticipantController extends AbstractActionController
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function registerPlayerPartieAction()
{
    $existingType = [
        'register',
        'unregister',
        'presence',
        'unpresence',
        'paid',
        'unpaid',
    ];

    // Vérification de la clé API
    $validationResult = $this->validateApiKey($this);
    if ($validationResult) {
        return $validationResult;
    }

    $request = $this->getRequest();
    /** @var \Laminas\Http\Request $request */

    // Vérification que la requête est de type POST
    if (!$request->isPost()) {
        return new JsonModel([
            'success' => false,
            'message' => 'Invalid request method. Use POST.',
        ]);
    }

    $data = json_decode($request->getContent(), true);

    // Vérification des champs obligatoires
    if (!isset($data['partie']) || !isset($data['iduser']) || !isset($data['type'])) {
        return new JsonModel([
            'success' => false,
            'message' => 'Required fields are missing: partie, iduser and type.',
        ]);
    }

    // Vérification si le type est valide
    if (!in_array($data['type'], $existingType)) {
        return new JsonModel([
            'success' => false,
            'message' => 'Invalid type. Allowed types: ' . implode(', ', $existingType),
        ]);
    }

    // Récupération des entités
    $partie = $this->entityManager->getRepository(Parties::class)->findOneBy(['id' => $data['partie']]);
    $user = $this->entityManager->getRepository(Users::class)->findOneBy(['id' => $data['iduser']]);
    $participant = $this->entityManager->getRepository(Participants::class)->findOneBy([
        'partie' => $data['partie'],
        'user' => $data['iduser'],
    ]);

    if (!$partie) {
        return new JsonModel([
            'success' => false,
            'message' => 'No partie found!',
        ]);
    }

    if (!$user) {
        return new JsonModel([
            'success' => false,
            'message' => 'User not found!',
        ]);
    }

    try {
        // Traitement selon le type
        switch ($data['type']) {
            case 'register':
                if ($participant) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Participation already exists!',
                    ]);
                }

                $newParticipant = new Participants();
                $newParticipant->setUser($data['iduser']);
                $newParticipant->setPartie($data['partie']);
                $newParticipant->setPresence(0);
                $newParticipant->setPaid(0);
                $this->entityManager->persist($newParticipant);
                $this->entityManager->flush();

                return new JsonModel([
                    'success' => true,
                    'message' => 'Participation created successfully.',
                    'partieId' => $partie->getId(),
                ]);

            case 'unregister':
                if (!$participant) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Participation does not exist!',
                    ]);
                }

                $this->entityManager->remove($participant);
                $this->entityManager->flush();

                return new JsonModel([
                    'success' => true,
                    'message' => 'Participation unregistered successfully.',
                ]);

            case 'presence':
                if (!$participant) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Participation does not exist!',
                    ]);
                }

                $participant->setPresence(1);
                $this->entityManager->flush();

                return new JsonModel([
                    'success' => true,
                    'message' => 'Presence marked successfully.',
                ]);

            case 'unpresence':
                if (!$participant) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Participation does not exist!',
                    ]);
                }

                $participant->setPresence(0);
                $this->entityManager->flush();

                return new JsonModel([
                    'success' => true,
                    'message' => 'Presence removed successfully.',
                ]);

            case 'paid':
                if (!$participant) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Participation does not exist!',
                    ]);
                }

                $participant->setPaid(1);
                $this->entityManager->flush();

                return new JsonModel([
                    'success' => true,
                    'message' => 'Payment marked successfully.',
                ]);

            case 'unpaid':
                if (!$participant) {
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Participation does not exist!',
                    ]);
                }

                $participant->setPaid(0);
                $this->entityManager->flush();

                return new JsonModel([
                    'success' => true,
                    'message' => 'Payment removed successfully.',
                ]);

            default:
                return new JsonModel([
                    'success' => false,
                    'message' => 'Unhandled type.',
                ]);
        }
    } catch (\Exception $e) {
        return new JsonModel([
            'success' => false,
            'message' => 'Error occurred: ' . $e->getMessage(),
        ]);
    }
}

}
