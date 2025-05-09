<?php

declare(strict_types=1);

namespace Parties\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use Parties\Entity\Participants;
use Parties\Entity\Parties;
use Users\Entity\Users;

use function PHPSTORM_META\type;

// use Users\Entity\Users;

class IndexController extends AbstractActionController
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function indexAction()
    {
        $allGames = $this->entityManager->getRepository(Parties::class)->findAll();
        var_dump($allGames);
        return new JsonModel();
    }

    public function transformEmailToIdUserAction()
    {
        $users = $this->entityManager->getRepository(Users::class)->findAll();
        $allParticipants = $this->entityManager->getRepository(Participants::class)->findAll();
        foreach($allParticipants as $participant){
            foreach($users as $user){
                if($user->getEmail() == $participant->getUserEmail()  ){
               
                    $participant->setUserId($user->getId());
                    $this->entityManager->persist($participant);
                    $this->entityManager->flush();
                    var_dump($participant);
                    var_dump($user->getId());
                    // var_dump($participant);
                    break;
                }
            }
        }
    
        return new JsonModel();
    }
    public function transformPartieIdForRealAction()
    {
        // $users = $this->entityManager->getRepository(Users::class)->findAll();
        $allParticipants = $this->entityManager->getRepository(Participants::class)->findAll();
        $allParties = $this->entityManager->getRepository(Parties::class)->findAll();

        foreach($allParticipants as $participant){
            foreach($allParties as $partie){
                
            }
        }

        //     foreach($users as $user){
        //         if($user->getEmail() == $participant->getUserEmail()  ){
               
        //             $participant->setUserId($user->getId());
        //             $this->entityManager->persist($participant);
        //             $this->entityManager->flush();
        //             var_dump($participant);
        //             var_dump($user->getId());
        //             // var_dump($participant);
        //             break;
        //         }
        //     }
        // }
    
        return new JsonModel();
    }

}
