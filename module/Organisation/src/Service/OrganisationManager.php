<?php
namespace Organisation\Service;

use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Organisation\Entity\PollOption;
use Organisation\Entity\PollVote;
use Organisation\Entity\Post;
use Organisation\Entity\Rsvp;
use Organisation\Repository\PostRepository;
use User\Entity\User;

/**
 * Publications de l'espace Organisation, votes et reponses aux evenements.
 */
class OrganisationManager
{
    public const OPTIONS_MIN = 2;
    public const OPTIONS_MAX = 10;
    // Format des champs date et heure des formulaires (input datetime-local)
    public const INPUT_FORMAT = 'Y-m-d\TH:i';

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function posts(): PostRepository
    {
        return $this->entityManager->getRepository(Post::class);
    }

    /**
     * Cree ($post null, type $type) ou modifie une publication.
     *
     * @param array $input champs POST : title, body, closes_at, starts_at, ends_at,
     *                     location, multiple, options[]
     * @return array{post: ?Post, errors: string[]}
     */
    public function save(?Post $post, string $type, User $author, array $input): array
    {
        $type = $post ? $post->getType() : (isset(Post::TYPES[$type]) ? $type : Post::TYPE_MESSAGE);
        $title = mb_substr(InputSanitizer::cleanString($input['title'] ?? ''), 0, 200);
        $body = InputSanitizer::cleanText($input['body'] ?? '');
        $errors = [];

        if (!mb_check_encoding($title . $body, 'UTF-8')) {
            return ['post' => $post, 'errors' => ['Le texte contient des caractères non reconnus.']];
        }
        if (mb_strlen($title) < 3 || mb_strlen($title) > Post::TITLE_MAX) {
            $errors[] = 'Le titre doit faire entre 3 et ' . Post::TITLE_MAX . ' caractères.';
        }
        if (mb_strlen($body) > Post::BODY_MAX) {
            $errors[] = 'Texte trop long (' . Post::BODY_MAX . ' caractères maximum).';
        }
        if ($type === Post::TYPE_MESSAGE && $body === '') {
            $errors[] = 'Le message est vide.';
        }

        [$closesAt, $error] = $this->readDate($input['closes_at'] ?? '', false, 'de clôture');
        if ($error) {
            $errors[] = $error;
        }

        $startsAt = $endsAt = null;
        $location = null;
        if ($type === Post::TYPE_EVENT) {
            [$startsAt, $error] = $this->readDate($input['starts_at'] ?? '', true, 'de début');
            if ($error) {
                $errors[] = $error;
            }
            [$endsAt, $error] = $this->readDate($input['ends_at'] ?? '', false, 'de fin');
            if ($error) {
                $errors[] = $error;
            } elseif ($startsAt && $endsAt && $endsAt < $startsAt) {
                $errors[] = 'La fin doit être après le début.';
            }
            $location = mb_substr(InputSanitizer::cleanString($input['location'] ?? ''), 0, Post::LOCATION_MAX);
        }

        $labels = [];
        $lockedOptions = $post && $post->isPoll() && $this->countVotes($post) > 0;
        if ($type === Post::TYPE_POLL && !$lockedOptions) {
            foreach ((array) ($input['options'] ?? []) as $raw) {
                $label = mb_substr(InputSanitizer::cleanString($raw), 0, PollOption::LABEL_MAX);
                if ($label !== '' && !in_array(mb_strtolower($label), array_map('mb_strtolower', $labels), true)) {
                    $labels[] = $label;
                }
            }
            if (count($labels) < self::OPTIONS_MIN || count($labels) > self::OPTIONS_MAX) {
                $errors[] = 'Un sondage a entre ' . self::OPTIONS_MIN . ' et ' . self::OPTIONS_MAX . ' choix différents.';
            }
        }

        if ($errors) {
            return ['post' => $post, 'errors' => $errors];
        }

        if (!$post) {
            $post = new Post($type, $author);
            $this->entityManager->persist($post);
        }
        $post->update($title, $body, $closesAt);
        if ($type === Post::TYPE_EVENT) {
            $post->setEvent($startsAt, $endsAt, $location);
        }
        if ($type === Post::TYPE_POLL && !$lockedOptions) {
            // Pas encore de vote : les choix sont remplaces tels que saisis
            $post->setMultiple(!empty($input['multiple']));
            $post->getOptions()->clear();
            foreach ($labels as $label) {
                $post->addOption($label);
            }
        }
        $this->entityManager->flush();
        return ['post' => $post, 'errors' => []];
    }

    public function delete(Post $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    public function togglePin(Post $post): void
    {
        $post->setPinned(!$post->isPinned());
        $this->entityManager->flush();
    }

    /**
     * Vote (remplace le precedent). Aucun choix coche : le vote est retire.
     *
     * @param int[] $optionIds
     * @return string|null erreur affichable
     */
    public function vote(Post $post, User $user, array $optionIds): ?string
    {
        if (!$post->isPoll() || $post->isClosed()) {
            return 'Ce sondage est clos.';
        }
        $optionIds = array_values(array_unique(array_map('intval', $optionIds)));
        if (!$post->isMultiple() && count($optionIds) > 1) {
            return 'Un seul choix possible.';
        }
        // Seulement la difference : Doctrine fait les INSERT avant les DELETE,
        // recreer un vote garde violerait la contrainte unique (option, membre)
        $kept = [];
        foreach ($this->votesOf($post, $user) as $vote) {
            if (in_array($vote->getOption()->getId(), $optionIds, true)) {
                $kept[] = $vote->getOption()->getId();
            } else {
                $this->entityManager->remove($vote);
            }
        }
        foreach ($post->getOptions() as $option) {
            if (in_array($option->getId(), $optionIds, true) && !in_array($option->getId(), $kept, true)) {
                $this->entityManager->persist(new PollVote($option, $user));
            }
        }
        $this->entityManager->flush();
        return null;
    }

    /**
     * @return string|null erreur affichable
     */
    public function answer(Post $post, User $user, string $answer): ?string
    {
        if (!$post->isEvent() || $post->isClosed()) {
            return 'Les réponses sont closes pour cet événement.';
        }
        $rsvp = $this->rsvpOf($post, $user);
        if ($rsvp) {
            $rsvp->setAnswer($answer);
        } else {
            $this->entityManager->persist(new Rsvp($post, $user, $answer));
        }
        $this->entityManager->flush();
        return null;
    }

    /**
     * @return int[] identifiants des choix votes par le membre
     */
    public function myOptionIds(Post $post, User $user): array
    {
        return array_map(fn (PollVote $vote) => $vote->getOption()->getId(), $this->votesOf($post, $user));
    }

    public function rsvpOf(Post $post, User $user): ?Rsvp
    {
        return $this->entityManager->getRepository(Rsvp::class)->findOneBy(['post' => $post, 'user' => $user]);
    }

    /**
     * Resultats d'un sondage : par choix, le nombre de voix et les votants.
     *
     * @return array{options: array<int, array{option: PollOption, count: int, voters: User[]}>, voters: int}
     */
    public function results(Post $post): array
    {
        $options = [];
        $voters = [];
        foreach ($post->getOptions() as $option) {
            $users = array_map(fn (PollVote $vote) => $vote->getUser(), $option->getVotes()->toArray());
            foreach ($users as $user) {
                $voters[$user->getIdUser()] = true;
            }
            $options[] = ['option' => $option, 'count' => count($users), 'voters' => $users];
        }
        return ['options' => $options, 'voters' => count($voters)];
    }

    /**
     * @return array{yes: User[], no: User[]} reponses a un evenement
     */
    public function answers(Post $post): array
    {
        $answers = ['yes' => [], 'no' => []];
        foreach ($post->getRsvps() as $rsvp) {
            $answers[$rsvp->isYes() ? 'yes' : 'no'][] = $rsvp->getUser();
        }
        return $answers;
    }

    public function countVotes(Post $post): int
    {
        $total = 0;
        foreach ($post->getOptions() as $option) {
            $total += $option->getVotes()->count();
        }
        return $total;
    }

    /**
     * @return PollVote[]
     */
    private function votesOf(Post $post, User $user): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(PollVote::class, 'v')
            ->innerJoin('v.option', 'o')
            ->where('o.post = :post')
            ->andWhere('v.user = :user')
            ->setParameter('post', $post)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{0: ?\DateTime, 1: ?string} date lue (champ datetime-local), erreur eventuelle
     */
    private function readDate($raw, bool $required, string $label): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [null, $required ? 'Indiquez la date ' . $label . '.' : null];
        }
        $date = \DateTime::createFromFormat(self::INPUT_FORMAT, $raw) ?: \DateTime::createFromFormat('Y-m-d\TH:i:s', $raw);
        if (!$date) {
            return [null, 'Date ' . $label . ' invalide.'];
        }
        return [$date, null];
    }
}
