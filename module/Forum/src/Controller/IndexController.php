<?php

declare(strict_types=1);

namespace Forum\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use Forum\Entity\Topic;

class IndexController extends AbstractActionController
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function indexAction()
    {
  
        return new JsonModel();
    }

    public function getAllTopicsAction()
    {
        $allTopics = $this->entityManager->getRepository(Topic::class)->findAll();
        dump($allTopics);
        return new JsonModel(

        ); 
    }


    public function getTopicAction()
    {
        $id = $this->params()->fromRoute('id', 0);
        return new JsonModel([
            'id'=>$id,
        ]);
    }

}
