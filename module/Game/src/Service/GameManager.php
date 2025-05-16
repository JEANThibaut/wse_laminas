<?php
namespace Game\Service;

use Game\Entity\Replique;
use Doctrine\ORM\EntityManager;

class GameManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addGame(array $data, $currentUser)
    {
        // $replique = new Replique();
        // $replique->setNomReplique($data['nom_replique']);
        // $replique->setTypeReplique($data['type_replique']);
        // $replique->setPuissance($data['puissance']);
        // $replique->setIdUser($currentUser->getIduser());

        // $this->entityManager->persist($replique);
        // $this->entityManager->flush();

        // return $replique;
    }


    public function updateGame($game, array $data)
    {
        // $replique->setNomReplique($data['nom_replique']);
        // $replique->setTypeReplique($data['type_replique']);
        // $replique->setPuissance($data['puissance']);

        // $this->entityManager->flush();
    }

    public function deleteGame( $game)
    {
        // $this->entityManager->remove($game);
        // $this->entityManager->flush();
    }
}
