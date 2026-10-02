<?php
namespace Game\Service;

use Game\Entity\Game;
use Game\Entity\GameRegister;
use Game\Entity\WaitingList;
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

    // Resultats de registerInGame() et confirmPendingRegister()
    public const RESULT_REGISTERED = 'registered';
    public const RESULT_QUEUED = 'queued';
    public const RESULT_CONFIRMED = 'confirmed';
    public const RESULT_ALREADY = 'already';
    public const RESULT_FULL = 'full';
    public const RESULT_NOT_OPEN = 'not_open';
    public const RESULT_CLOSED = 'closed';

    // Heure de reference d'une partie : la date stockee n'a pas d'heure fiable
    // (le formulaire ne saisit que le jour), on prend l'heure d'accueil.
    private const GAME_START_TIME = '08:00';
    private const TIMEZONE = 'Europe/Paris';
    // Ouverture de la confirmation pour la file d'attente, en heures avant la partie
    private const CONFIRM_HOURS_ALREADY_CAME = 48;
    private const CONFIRM_HOURS_NEVER_CAME = 24;

    /**
     * Presences (paid = 1) et absences (paid = 0) d'un joueur sur les parties
     * passees, inscriptions actives non membres (member != 1).
     *
     * @return array{presences: int, absences: int}
     */
    public function getAttendance(User $user): array
    {
        $stats = $this->entityManager->getRepository(GameRegister::class)
            ->getParticipationStatsByUser($user->getIdUser(), true)[$user->getIdUser()] ?? null;
        $presences = $stats['validated'] ?? 0;

        return [
            'presences' => $presences,
            'absences' => ($stats['registered'] ?? 0) - $presences,
        ];
    }

    /**
     * Un joueur deja absent a une partie passe par la file d'attente.
     * Membres, admins et GOD n'y passent jamais.
     */
    public function mustQueue(User $user): bool
    {
        // Desactive pour l'instant : la file d'attente se gere a la main
        // depuis la fiche d'une partie, le temps de fiabiliser le comptage des
        // absences.
        return false;

        if ($user->getIsMember() || $user->hasAdminAccess()) {
            return false;
        }
        return $this->getAttendance($user)['absences'] >= 1;
    }

    /**
     * Inscrits d'une partie qui releveraient de la file d'attente avec les
     * criteres actuels (memes regles que mustQueue).
     *
     * @return GameRegister[]
     */
    public function findRegistersToQueue(Game $game): array
    {
        $repository = $this->entityManager->getRepository(GameRegister::class);
        $stats = $repository->getParticipationStatsByUser(null, true);
        $registers = $repository->findBy(['game' => $game, 'status' => GameRegister::STATUS_ACTIVE], ['idregister' => 'ASC']);

        return array_values(array_filter($registers, function (GameRegister $register) use ($stats) {
            $user = $register->getUser();
            if ($user->getIsMember() || $user->hasAdminAccess()) {
                return false;
            }
            $stat = $stats[$user->getIdUser()] ?? ['registered' => 0, 'validated' => 0];
            return $stat['registered'] - $stat['validated'] >= 1;
        }));
    }

    /**
     * Place en file d'attente les inscrits d'une partie qui en relevent.
     *
     * @return GameRegister[] les inscriptions deplacees
     */
    public function generateQueue(Game $game): array
    {
        $registers = $this->findRegistersToQueue($game);
        foreach ($registers as $register) {
            $register->setStatus(GameRegister::STATUS_PENDING);
            $register->setArrivedNumber(0);
        }
        $this->entityManager->flush();

        return $registers;
    }

    public function getGameStart(Game $game): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            $game->getDate()->format('Y-m-d') . ' ' . self::GAME_START_TIME,
            new \DateTimeZone(self::TIMEZONE)
        );
    }

    /**
     * Moment a partir duquel un joueur en file d'attente peut confirmer :
     * 48 h avant s'il est deja venu au moins une fois, 24 h sinon.
     */
    public function getConfirmationOpening(Game $game, User $user): \DateTimeImmutable
    {
        $hours = $this->getAttendance($user)['presences'] >= 1
            ? self::CONFIRM_HOURS_ALREADY_CAME
            : self::CONFIRM_HOURS_NEVER_CAME;

        return $this->getGameStart($game)->modify("-{$hours} hours");
    }

    public function isFull(Game $game): bool
    {
        return $this->entityManager->getRepository(GameRegister::class)->countActiveRegisters($game) >= $game->getPlayerMax();
    }

    /**
     * Inscrit le joueur, ou le place en file d'attente s'il a deja ete absent.
     * La file d'attente reste ouverte meme quand la partie est complete.
     *
     * @return string self::RESULT_*
     */
    public function registerInGame($game, $currentUser): string
    {
        $user = $this->entityManager->getRepository(User::class)->find($currentUser->getIdUser());
        $queued = $this->mustQueue($user);

        return $this->entityManager->wrapInTransaction(function () use ($game, $user, $queued) {
            // Verrou sur la partie : deux inscriptions simultanees ne peuvent
            // pas depasser la limite de places
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);
            $repository = $this->entityManager->getRepository(GameRegister::class);

            if ($repository->findCurrentRegister($game, $user)) {
                return self::RESULT_ALREADY;
            }
            if (!$queued && $this->isFull($game)) {
                return self::RESULT_FULL;
            }

            $register = new GameRegister();
            $register->setUser($user);
            $register->setGame($game);
            $register->setPaid(0);
            $register->setArrivedNumber(0);
            $register->setMember($user->getIsMember());
            $register->setStatus($queued ? GameRegister::STATUS_PENDING : GameRegister::STATUS_ACTIVE);
            $this->entityManager->persist($register);
            $this->entityManager->flush();

            return $queued ? self::RESULT_QUEUED : self::RESULT_REGISTERED;
        });
    }

    /**
     * Le joueur en file d'attente confirme sa venue : il obtient une place si
     * la confirmation est ouverte pour lui et qu'il en reste.
     *
     * @return string self::RESULT_*
     */
    public function confirmPendingRegister(GameRegister $register): string
    {
        $game = $register->getGame();
        $now = new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE));
        if ($now < $this->getConfirmationOpening($game, $register->getUser())) {
            return self::RESULT_NOT_OPEN;
        }
        if ($now >= $this->getGameStart($game)) {
            return self::RESULT_CLOSED;
        }

        return $this->activatePendingRegister($register);
    }

    /**
     * Inscription depuis la file d'attente par un admin : sans condition de
     * date, mais dans la limite des places.
     *
     * @return string self::RESULT_*
     */
    public function adminConfirmPendingRegister(GameRegister $register): string
    {
        return $this->activatePendingRegister($register);
    }

    private function activatePendingRegister(GameRegister $register): string
    {
        $game = $register->getGame();

        return $this->entityManager->wrapInTransaction(function () use ($register, $game) {
            $this->entityManager->lock($game, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->refresh($register);
            if (!$register->isPending()) {
                return self::RESULT_ALREADY;
            }
            if ($this->isFull($game)) {
                return self::RESULT_FULL;
            }
            $register->setStatus(GameRegister::STATUS_ACTIVE);
            $this->entityManager->flush();

            return self::RESULT_CONFIRMED;
        });
    }

    /**
     * File d'attente d'une partie classee par priorite : le plus de presences
     * d'abord, puis le moins d'absences, puis l'ordre d'inscription.
     *
     * @return array<int, array{register: GameRegister, presences: int, absences: int, opening: \DateTimeImmutable}>
     */
    public function getPendingQueue(Game $game): array
    {
        $stats = $this->entityManager->getRepository(GameRegister::class)->getParticipationStatsByUser(null, true);
        $queue = [];
        foreach ($this->entityManager->getRepository(GameRegister::class)->findPendingRegisters($game) as $register) {
            $stat = $stats[$register->getUser()->getIdUser()] ?? ['registered' => 0, 'validated' => 0];
            $queue[] = [
                'register' => $register,
                'presences' => $stat['validated'],
                'absences' => $stat['registered'] - $stat['validated'],
                'opening' => $this->getConfirmationOpening($game, $register->getUser()),
            ];
        }
        usort($queue, fn ($a, $b) => [$b['presences'], $a['absences'], $a['register']->getIdregister()]
            <=> [$a['presences'], $b['absences'], $b['register']->getIdregister()]);

        return $queue;
    }

    public function registerInWaitingList($currentUser,$game, $count){
        
        $newWaitingList = new WaitingList;
        $newWaitingList->setGameId($game->getIdGame());
        $newWaitingList->setUserId($currentUser->getIdUser());
        $newWaitingList->setEmailSend(0);
        $newWaitingList->setIsValidate(0);
        $newWaitingList->setOrderList($count +1);
        $this->entityManager->persist($newWaitingList);
        $this->entityManager->flush();

    }

}
