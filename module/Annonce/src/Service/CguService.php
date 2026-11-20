<?php
namespace Annonce\Service;

use Annonce\Entity\CguAcceptance;
use Annonce\Entity\CguVersion;
use Doctrine\ORM\EntityManager;
use User\Entity\User;

/**
 * CGU de l'espace Petites annonces : version en vigueur (la plus haute),
 * acceptation une fois par version, publication d'une nouvelle version.
 */
class CguService
{
    private EntityManager $entityManager;
    private ?CguVersion $current = null;
    private bool $currentLoaded = false;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function current(): ?CguVersion
    {
        if (!$this->currentLoaded) {
            $this->current = $this->entityManager->getRepository(CguVersion::class)->findOneBy([], ['version' => 'DESC']);
            $this->currentLoaded = true;
        }
        return $this->current;
    }

    public function hasAcceptedCurrent(User $user): bool
    {
        $current = $this->current();
        if (!$current) {
            return false;
        }
        return $this->entityManager->getRepository(CguAcceptance::class)
            ->findOneBy(['user' => $user, 'cguVersion' => $current]) !== null;
    }

    public function acceptCurrent(User $user, string $ip): void
    {
        $current = $this->current();
        if (!$current || $this->hasAcceptedCurrent($user)) {
            return;
        }
        $this->entityManager->persist(new CguAcceptance($user, $current, $ip));
        $this->entityManager->flush();
    }

    /**
     * Nouvelle version : tous les membres devront l'accepter a nouveau.
     */
    public function publish(string $content): CguVersion
    {
        $current = $this->current();
        $version = new CguVersion($current ? $current->getVersion() + 1 : 1, $content);
        $this->entityManager->persist($version);
        $this->entityManager->flush();
        $this->current = $version;
        return $version;
    }

    /**
     * @return array<int, array{version: CguVersion, count: int}> versions, la plus recente d'abord,
     *                                                            avec leur nombre d'acceptations
     */
    public function history(): array
    {
        $counts = [];
        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(a.cguVersion) AS versionId, COUNT(a.id) AS total')
            ->from(CguAcceptance::class, 'a')
            ->groupBy('a.cguVersion')
            ->getQuery()
            ->getArrayResult();
        foreach ($rows as $row) {
            $counts[(int) $row['versionId']] = (int) $row['total'];
        }

        $history = [];
        foreach ($this->entityManager->getRepository(CguVersion::class)->findBy([], ['version' => 'DESC']) as $version) {
            $history[] = ['version' => $version, 'count' => $counts[$version->getId()] ?? 0];
        }
        return $history;
    }

    /**
     * @return CguAcceptance[] dernieres acceptations de la version en vigueur
     */
    public function latestAcceptances(int $limit = 50): array
    {
        $current = $this->current();
        if (!$current) {
            return [];
        }
        return $this->entityManager->getRepository(CguAcceptance::class)
            ->findBy(['cguVersion' => $current], ['acceptedAt' => 'DESC'], $limit);
    }
}
