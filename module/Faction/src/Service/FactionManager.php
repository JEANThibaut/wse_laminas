<?php
namespace Faction\Service;

use Doctrine\ORM\EntityManager;
use Faction\Entity\Faction;

class FactionManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAllFactions()
    {
        return $this->entityManager->getRepository(Faction::class)->findAll();
    }

    public function addFaction($data)
    {
        $faction = new Faction();
        $faction->setName($data['name'] ?? '');
        $faction->setDescription($data['description'] ?? '');
        
        $this->entityManager->persist($faction);
        $this->entityManager->flush();
        
        return $faction;
    }

    public function updateFaction($faction, $data)
    {
        $faction->setName($data['name'] ?? $faction->getName());
        $faction->setDescription($data['description'] ?? $faction->getDescription());
        
        $this->entityManager->flush();
        
        return $faction;
    }

    public function deleteFaction($faction)
    {
        $this->entityManager->remove($faction);
        $this->entityManager->flush();
    }

    public function joinFaction($faction, $user)
    {
        $user->setFaction($faction->getIdfaction());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return true;
    }
}
