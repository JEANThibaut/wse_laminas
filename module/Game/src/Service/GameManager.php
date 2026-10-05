<?php
namespace Game\Service;

use Game\Entity\Game;
use Game\Entity\GameRegister;
use Game\Entity\QueueEntry;
use User\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;

class GameManager
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addGame(array $data)
    {
           $date = \DateTime::createFromFormat('d/m/Y', $data['date']);
            $newGame = new Game;
            $newGame->setDate($date);
            $newGame->setPlayerMax($data['player_max']);
            $newGame->setStatus((int)$data['status']);
            $this->entityManager->persist($newGame);
            $this->entityManager->flush();
            return $newGame;
    }


    public function editGame($game, array $data)
    {
            $date = \DateTime::createFromFormat('d/m/Y', $data['date']);
            $game->setDate($date);
            $game->setPlayerMax($data['player_max']);
            $game->setStatus((int)$data['status']);
            $this->entityManager->flush();
            return $game;
    }

    public function deleteGame( $game)
    {
        $this->entityManager->remove($game);
        $this->entityManager->flush();
        return true;
    }

    // Resultats de registerInGame() et adminAddPlayer()
    public const RESULT_REGISTERED = 'registered';
    public const RESULT_ALREADY = 'already';
    public const RESULT_FULL = 'full';

    // Heure de reference d'une partie : la date stockee n'a pas d'heure fiable
    // (le formulaire ne saisit que le jour), on prend l'heure d'accueil.
    private const GAME_START_TIME = '08:00';
    public const TIMEZONE = 'Europe/Paris';

    public function getGameStart(Game $game): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            $game->getDate()->format('Y-m-d') . ' ' . self::GAME_START_TIME,
            new \DateTimeZone(self::TIMEZONE)
        );
    }

    /**
     * Places encore libres : le maximum, moins les inscrits et les places
     * proposees a la file d'attente (reservees tant qu'elles ne sont pas
     * acceptees ou expirees). Negatif si un admin a depasse le maximum.
     */
    public function countFreePlaces(Game $game): int
    {
        return (int) $game->getPlayerMax()
            - $this->entityManager->getRepository(GameRegister::class)->countActiveRegisters($game)
            - $this->entityManager->getRepository(QueueEntry::class)->countOffered($game);
    }

    public function isFull(Game $game): bool
    {
        return $this->countFreePlaces($game) <= 0;
    }

    /**
     * Inscrit le joueur s'il reste une place libre. Une place proposee a la
     * file d'attente n'est pas libre : on ne passe pas devant la file.
     *
     * @return string self::RESULT_*
     */
    public function registerInGame($game, $currentUser): string
    {
        $user = $this->entityManager->getRepository(User::class)->find($currentUser->getIdUser());

        return $this->entityManager->wrapInTransaction(function () use ($game, $user) {
            // Verrou sur la partie : deux inscriptions simultanees ne peuvent
            // pas depasser la limite de places
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);

            if ($this->entityManager->getRepository(GameRegister::class)->findCurrentRegister($game, $user)) {
                return self::RESULT_ALREADY;
            }
            if ($this->isFull($game)) {
                return self::RESULT_FULL;
            }
            $this->createRegister($game, $user);

            return self::RESULT_REGISTERED;
        });
    }

    /**
     * Inscription manuelle d'un joueur par un admin, sans limite de places ni
     * condition de date. La file d'attente (QueueManager) est mise a jour par
     * l'appelant.
     *
     * @return string self::RESULT_REGISTERED ou RESULT_ALREADY
     */
    public function adminAddPlayer(Game $game, User $user): string
    {
        return $this->entityManager->wrapInTransaction(function () use ($game, $user) {
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);

            if ($this->entityManager->getRepository(GameRegister::class)->findCurrentRegister($game, $user)) {
                return self::RESULT_ALREADY;
            }
            $this->createRegister($game, $user);

            return self::RESULT_REGISTERED;
        });
    }

    /**
     * Nouvelle inscription active, a appeler dans une transaction qui verrouille la partie.
     */
    public function createRegister(Game $game, User $user): GameRegister
    {
        $register = new GameRegister();
        $register->setUser($user);
        $register->setGame($game);
        $register->setPaid(0);
        $register->setArrivedNumber(0);
        $register->setMember($user->getIsMember());
        $register->setStatus(GameRegister::STATUS_ACTIVE);
        $this->entityManager->persist($register);
        $this->entityManager->flush();

        return $register;
    }
}
