<?php
namespace Actus\Service;

use Actus\Entity\Actus;

use Doctrine\ORM\EntityManager;

class ActusManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addActus(array $data): Actus
    {
        $actus = new Actus();
        $this->applyData($actus, $data);
        $this->entityManager->persist($actus);
        $this->entityManager->flush();

        return $actus;
    }

    public function editActus(Actus $actus, array $data): Actus
    {
        $this->applyData($actus, $data);
        $this->entityManager->flush();

        return $actus;
    }

    public function deleteActus(Actus $actus): bool
    {
        $this->entityManager->remove($actus);
        $this->entityManager->flush();

        return true;
    }

    private function applyData(Actus $actus, array $data): void
    {
        $actus->setTitre($this->normalize($data['titre'] ?? null));
        $actus->setType($this->normalize($data['type'] ?? null));
        $actus->setContenu($this->normalize($data['contenu'] ?? null));
        $actus->setLien($this->normalize($data['lien'] ?? null));

        $dateValue = $this->normalize($data['date'] ?? null);
        if ($dateValue !== null) {
            $date = \DateTime::createFromFormat('d/m/Y', $dateValue);
            if ($date) {
                $actus->setDate($date);
            }
        }

        if (! $actus->getDate()) {
            $actus->setDate(new \DateTime());
        }

        if (array_key_exists('is_active', $data)) {
            $actus->setIsActive((int) $data['is_active']);
        } elseif ($actus->getIsActive() === null) {
            $actus->setIsActive(1);
        }
    }

    private function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

}
