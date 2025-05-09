<?php
namespace Profil\Service;

use Profil\Entity\Replique;
use Doctrine\ORM\EntityManager;

class RepliqueManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addReplique(array $data, $currentUser)
    {
        $replique = new Replique();
        $replique->setNomReplique($data['nom_replique']);
        $replique->setTypeReplique($data['type_replique']);
        $replique->setPuissance($data['puissance']);
        $replique->setIdUser($currentUser->getIduser());

        $this->entityManager->persist($replique);
        $this->entityManager->flush();

        return $replique;
    }


    public function updateReplique(Replique $replique, array $data)
    {
        $replique->setNomReplique($data['nom_replique']);
        $replique->setTypeReplique($data['type_replique']);
        $replique->setPuissance($data['puissance']);

        $this->entityManager->flush();
    }

    public function deleteReplique(Replique $replique)
    {
        $this->entityManager->remove($replique);
        $this->entityManager->flush();
    }
}
