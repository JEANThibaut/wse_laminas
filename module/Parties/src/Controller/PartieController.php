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

class PartieController extends AbstractActionController
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function indexAction()
    {

        // Vérification de la clé API
        $validationResult = $this->validateApiKey($this);
        if ($validationResult) {
            return $validationResult;
        }

        $allGames = $this->entityManager->getRepository(Parties::class)->findAll();
        $data=[];
        foreach($allGames as $game){
            $participants = $this->entityManager->getRepository(Participants::class)->findBy(['partie'=>$game->getId()]);
            $players =[];
            if(count($participants)>0){
                foreach($participants as $participant){
                    $user = $this->entityManager->getRepository(Users::class)->findOneBy(['id'=>$participant->getUser()]);
                    $players[]=[
                        'id'=>$user->getId(),
                        'firstname'=>$user->getFirstname(),
                        'lastname'=>$user->getLastname(),
                        'roles'=>$user->getRole(),
                    ];
                } 
            }
            $data[]=[
                'id'=>$game->getId(),
                'date'=>$game->getDate(),
                'joueursMax'=>$game->getJoueursMax(),
                'isActive'=>$game->getIsActive(),
                'photosUrl'=>$game->getPhotosUrl(),
                'players'=>$players,
            ];
        }
        return new JsonModel([
             "data" => $data,
        ]);
    }


    public function getPartieAction()
    {   

        // Vérification de la clé API
        $validationResult = $this->validateApiKey($this);
        if ($validationResult) {
            return $validationResult;
        }


        $id = $this->params()->fromRoute('id', 0);
        $game = $this->entityManager->getRepository(Parties::class)->findOneBy(['id'=>$id]);
        $participants = $this->entityManager->getRepository(Participants::class)->findBy(['partie'=>$game->getId()]);
        $data=[];
        $players =[];
        if(count($participants)>0){
            foreach($participants as $participant){
                $user = $this->entityManager->getRepository(Users::class)->findOneBy(['id'=>$participant->getUser()]);
                $players[]=[
                    'id'=>$user->getId(),
                    'firstname'=>$user->getFirstname(),
                    'lastname'=>$user->getLastname(),
                    'roles'=>$user->getRole(),
                ];
            } 
        }

        $data[]=[
            'id'=>$game->getId(),
            'date'=>$game->getDate(),
            'joueursMax'=>$game->getJoueursMax(),
            'isActive'=>$game->getIsActive(),
            'photosUrl'=>$game->getPhotosUrl(),
            'players'=>$players,
        ];
        return new JsonModel([
             "data" => $data,
        ]);
    }


public function addPartieAction()
{
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
    if (!isset($data['date']) || !isset($data['joueursMax'])) {
        return new JsonModel([
            'success' => false,
            'message' => 'Required fields are missing: date and joueursMax.',
        ]);
    }

    try {
        $date = new \DateTime($data['date']);

        // Vérification si une partie existe déjà pour cette date
        $existingPartie = $this->entityManager->getRepository(Parties::class)
            ->findOneBy(['date' => $date]);

        if ($existingPartie) {
            return new JsonModel([
                'success' => false,
                'message' => 'A partie already exists for the specified date.',
            ]);
        }

        // Création d'une nouvelle partie
        $partie = new Parties();
        $partie->setDate($date);
        $partie->setJoueursMax($data['joueursMax']);
        $partie->setIsActive(1);
        $partie->setPhotosUrl($data['photosUrl'] ?? ""); // Correction pour photosUrl

        $this->entityManager->persist($partie);
        $this->entityManager->flush();

        return new JsonModel([
            'success' => true,
            'message' => 'Partie created successfully.',
            'partieId' => $partie->getId(),
        ]);
    } catch (\Exception $e) {
        return new JsonModel([
            'success' => false,
            'message' => 'Error occurred: ' . $e->getMessage(),
        ]);
    }
}


public function getNextPartieAction()
{
    // Vérification de la clé API
    $validationResult = $this->validateApiKey($this);
    if ($validationResult) {
        return $validationResult;
    }

    $request = $this->getRequest();
    /** @var \Laminas\Http\Request $request */

    // Vérification que la requête est de type GET
    if (!$request->isGet()) {
        return new JsonModel([
            'success' => false,
            'message' => 'Invalid request method. Use GET.',
        ]);
    }

    try {
        $currentDate = new \DateTime(); // Date actuelle côté serveur

        // Rechercher la prochaine partie après la date actuelle
        $nextPartie = $this->entityManager->getRepository(Parties::class)
            ->createQueryBuilder('p')
            ->where('p.date > :currentDate')
            ->setParameter('currentDate', $currentDate)
            ->andWhere('p.isActive = 1')
            ->orderBy('p.date', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$nextPartie) {
            return new JsonModel([
                'success' => true,
                'data' => null,
                'message' => 'No upcoming partie found.',
            ]);
        }

        // Préparer les données pour la réponse
        $data = [
            'id' => $nextPartie->getId(),
            'date' => $nextPartie->getDate()->format('Y-m-d H:i:s'),
            'joueursMax' => $nextPartie->getJoueursMax(),
            'isActive' => $nextPartie->getIsActive(),
            'photosUrl' => $nextPartie->getPhotosUrl(),
        ];

        return new JsonModel([
            'success' => true,
            'data' => $data,
        ]);
    } catch (\Exception $e) {
        return new JsonModel([
            'success' => false,
            'message' => 'Error occurred: ' . $e->getMessage(),
        ]);
    }
}

public function deletePartieAction()
{
    // Vérification de la clé API
    $validationResult = $this->validateApiKey($this);
    if ($validationResult) {
        return $validationResult;
    }

    $request = $this->getRequest();
    /** @var \Laminas\Http\Request $request */

    // Vérification que la requête est de type DELETE
    if (!$request->isPost()) {
        return new JsonModel([
            'success' => false,
            'message' => 'Invalid request method. Use POST.',
        ]);
    }

    $data = json_decode($request->getContent(), true);
    
    // Vérification des champs obligatoires
    if (!isset($data['id'])) {
        return new JsonModel([
            'success' => false,
            'message' => 'Required fields are missing: id.',
        ]);
    }
    $id = $data['id'];

    try {
        // Récupérer la partie à supprimer
        $partie = $this->entityManager->getRepository(Parties::class)->find($id);

        if (!$partie) {
            return new JsonModel([
                'success' => false,
                'message' => 'Partie not found.',
            ]);
        }

        // Supprimer la partie
        $this->entityManager->remove($partie);
        $this->entityManager->flush();

        return new JsonModel([
            'success' => true,
            'message' => 'Partie deleted successfully.',
        ]);
    } catch (\Exception $e) {
        return new JsonModel([
            'success' => false,
            'message' => 'Error occurred: ' . $e->getMessage(),
        ]);
    }
}

    
}



