<?php
namespace Organisation\Controller;

use Application\Service\AuthService;
use Application\Util\InputSanitizer;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Organisation\Entity\Post;
use Organisation\Entity\Rsvp;
use Organisation\Service\OrganisationAccess;
use Organisation\Service\OrganisationManager;
use Organisation\Service\OrganisationNotifier;
use User\Entity\User;

/**
 * Espace membres > Organisation : fil des publications, calendrier des
 * evenements, votes et reponses ; creation et modification pour les comptes
 * autorises a publier.
 */
class OrganisationController extends AbstractActionController
{
    private const PER_PAGE = 15;

    private AuthService $authService;
    private OrganisationAccess $access;
    private OrganisationManager $manager;
    private OrganisationNotifier $notifier;

    public function __construct(AuthService $authService, OrganisationAccess $access, OrganisationManager $manager, OrganisationNotifier $notifier)
    {
        $this->authService = $authService;
        $this->access = $access;
        $this->manager = $manager;
        $this->notifier = $notifier;
    }

    public function indexAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $page = max(1, InputSanitizer::cleanInt($this->params()->fromQuery('page', 1)));
        $type = (string) $this->params()->fromQuery('type', '');
        $type = isset(Post::TYPES[$type]) ? $type : null;
        $posts = $this->manager->posts()->feed($page, self::PER_PAGE, $type);

        return $this->page('organisation/index', [
            'posts' => $posts,
            'page' => $page,
            'pages' => max(1, (int) ceil(count($posts) / self::PER_PAGE)),
            'type' => $type,
            'upcoming' => $type === null && $page === 1 ? $this->manager->posts()->upcomingEvents() : [],
            'canEdit' => $this->access->canEdit($this->currentUser()),
        ]);
    }

    /**
     * Calendrier d'un mois (?mois=AAAA-MM) et ses evenements.
     */
    public function calendarAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $month = \DateTime::createFromFormat('!Y-m', (string) $this->params()->fromQuery('mois', '')) ?: new \DateTime('first day of this month');
        $month->setDate((int) $month->format('Y'), (int) $month->format('m'), 1)->setTime(0, 0);
        $next = (clone $month)->modify('+1 month');
        $events = $this->manager->posts()->eventsBetween($month, $next);

        $byDay = [];
        foreach ($events as $event) {
            $byDay[(int) $event->getStartsAt()->format('j')][] = $event;
        }

        return $this->page('organisation/calendar', [
            'month' => $month,
            'previous' => (clone $month)->modify('-1 month'),
            'next' => $next,
            'events' => $events,
            'byDay' => $byDay,
            'canEdit' => $this->access->canEdit($this->currentUser()),
        ]);
    }

    public function viewAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        $post = $this->findPost();
        if (!$post) {
            $this->flashMessenger()->addErrorMessage('Publication introuvable.');
            return $this->redirect()->toRoute('organisation');
        }
        $canEdit = $this->access->canEdit($user);
        $myOptions = $post->isPoll() ? $this->manager->myOptionIds($post, $user) : [];

        return $this->page('organisation/view', [
            'post' => $post,
            'canEdit' => $canEdit,
            'myOptions' => $myOptions,
            // Resultats visibles apres avoir vote, une fois clos, ou pour ceux qui publient
            'showResults' => $post->isPoll() && ($myOptions || $post->isClosed() || $canEdit),
            'results' => $post->isPoll() ? $this->manager->results($post) : null,
            'myAnswer' => $post->isEvent() ? $this->manager->rsvpOf($post, $user) : null,
            'answers' => $post->isEvent() ? $this->manager->answers($post) : null,
        ]);
    }

    public function newAction()
    {
        if ($redirect = $this->guard(true)) {
            return $redirect;
        }
        $type = (string) $this->params()->fromRoute('type');
        if (!isset(Post::TYPES[$type])) {
            return $this->redirect()->toRoute('organisation');
        }
        return $this->handleForm(null, $type);
    }

    public function editAction()
    {
        if ($redirect = $this->guard(true)) {
            return $redirect;
        }
        $post = $this->findPost();
        if (!$post) {
            $this->flashMessenger()->addErrorMessage('Publication introuvable.');
            return $this->redirect()->toRoute('organisation');
        }
        return $this->handleForm($post, $post->getType());
    }

    public function deleteAction()
    {
        if ($redirect = $this->guard(true)) {
            return $redirect;
        }
        if ($this->getRequest()->isPost() && ($post = $this->findPost())) {
            $this->manager->delete($post);
            $this->flashMessenger()->addSuccessMessage('Publication supprimée.');
        }
        return $this->redirect()->toRoute('organisation');
    }

    public function pinAction()
    {
        if ($redirect = $this->guard(true)) {
            return $redirect;
        }
        if ($this->getRequest()->isPost() && ($post = $this->findPost())) {
            $this->manager->togglePin($post);
            $this->flashMessenger()->addSuccessMessage($post->isPinned() ? 'Publication épinglée en haut.' : 'Publication désépinglée.');
            return $this->redirect()->toRoute('organisation-view', ['id' => $post->getId()]);
        }
        return $this->redirect()->toRoute('organisation');
    }

    public function voteAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $request = $this->getRequest();
        $post = $request->isPost() ? $this->findPost() : null;
        if (!$post) {
            return $this->redirect()->toRoute('organisation');
        }
        $error = $this->manager->vote($post, $this->currentUser(), (array) $request->getPost('options', []));
        $error ? $this->flashMessenger()->addErrorMessage($error) : $this->flashMessenger()->addSuccessMessage('Vote enregistré.');
        return $this->redirect()->toRoute('organisation-view', ['id' => $post->getId()]);
    }

    public function answerAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $request = $this->getRequest();
        $post = $request->isPost() ? $this->findPost() : null;
        if (!$post) {
            return $this->redirect()->toRoute('organisation');
        }
        $answer = $request->getPost('answer') === Rsvp::YES ? Rsvp::YES : Rsvp::NO;
        $error = $this->manager->answer($post, $this->currentUser(), $answer);
        $error ? $this->flashMessenger()->addErrorMessage($error) : $this->flashMessenger()->addSuccessMessage(
            $answer === Rsvp::YES ? 'C’est noté : vous participez.' : 'C’est noté : vous ne participez pas.'
        );
        return $this->redirect()->toRoute('organisation-view', ['id' => $post->getId()]);
    }

    private function handleForm(?Post $post, string $type)
    {
        $request = $this->getRequest();
        $errors = [];
        $values = $this->formValues($post);
        if ($request->isPost()) {
            $input = $request->getPost()->toArray();
            $result = $this->manager->save($post, $type, $this->currentUser(), $input);
            if (!$result['errors']) {
                $saved = $result['post'];
                $url = $this->url()->fromRoute('organisation-view', ['id' => $saved->getId()]);
                $message = $post ? 'Publication enregistrée.' : 'Publication ajoutée.';
                // Notification : nouvelle publication seulement, si la case est cochee
                if (!$post && !empty($input['notify'])) {
                    $report = $this->notifier->notifyNew($saved, $this->currentUser(), $url);
                    $message .= $report['sent'] > 0
                        ? ' Notification envoyée (' . $report['recipients'] . ' membre(s), ' . $report['sent'] . ' appareil(s)).'
                        : ' Aucun membre à notifier (notifications non activées ou aucun appareil abonné).';
                }
                $this->flashMessenger()->addSuccessMessage($message);
                return $this->redirect()->toRoute('organisation-view', ['id' => $saved->getId()]);
            }
            $errors = $result['errors'];
            $values = [
                'title' => InputSanitizer::cleanString($input['title'] ?? ''),
                'body' => InputSanitizer::cleanText($input['body'] ?? ''),
                'closes_at' => InputSanitizer::cleanString($input['closes_at'] ?? ''),
                'starts_at' => InputSanitizer::cleanString($input['starts_at'] ?? ''),
                'ends_at' => InputSanitizer::cleanString($input['ends_at'] ?? ''),
                'location' => InputSanitizer::cleanString($input['location'] ?? ''),
                'multiple' => !empty($input['multiple']),
                'options' => array_map([InputSanitizer::class, 'cleanString'], (array) ($input['options'] ?? [])),
            ];
        }

        return $this->page('organisation/form', [
            'post' => $post,
            'type' => $type,
            'values' => $values,
            'errors' => $errors,
            'optionsLocked' => $post && $post->isPoll() && $this->manager->countVotes($post) > 0,
        ]);
    }

    private function formValues(?Post $post): array
    {
        $format = fn (?\DateTime $date) => $date ? $date->format(OrganisationManager::INPUT_FORMAT) : '';
        if (!$post) {
            return ['title' => '', 'body' => '', 'closes_at' => '', 'starts_at' => '', 'ends_at' => '', 'location' => '', 'multiple' => false, 'options' => ['', '']];
        }
        return [
            'title' => $post->getTitle(),
            'body' => $post->getBody(),
            'closes_at' => $format($post->getClosesAt()),
            'starts_at' => $format($post->getStartsAt()),
            'ends_at' => $format($post->getEndsAt()),
            'location' => (string) $post->getLocation(),
            'multiple' => $post->isMultiple(),
            'options' => array_map(fn ($option) => $option->getLabel(), $post->getOptions()->toArray()),
        ];
    }

    /**
     * Redirection si le compte n'a pas acces (ou ne peut pas publier, $edition), sinon null.
     */
    private function guard(bool $edition = false)
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirect()->toRoute('login');
        }
        if (!$this->access->canAccess($user) || ($edition && !$this->access->canEdit($user))) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }
        return null;
    }

    private function findPost(): ?Post
    {
        $id = InputSanitizer::cleanInt($this->params()->fromRoute('id') ?: $this->getRequest()->getPost('id'));
        return $id > 0 ? $this->manager->posts()->find($id) : null;
    }

    private function currentUser(): ?User
    {
        $user = $this->authService->getIdentity();
        return $user instanceof User ? $user : null;
    }

    private function page(string $template, array $variables): ViewModel
    {
        $this->layout()->setVariable('activeMenu', 'organisation');
        $view = new ViewModel($variables);
        $view->setTemplate($template);
        return $view;
    }
}
