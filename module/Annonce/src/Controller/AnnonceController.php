<?php
namespace Annonce\Controller;

use Annonce\Entity\Annonce;
use Annonce\Entity\AnnoncePhoto;
use Annonce\Entity\AnnonceTag;
use Annonce\Service\AnnonceAccess;
use Annonce\Service\AnnonceManager;
use Annonce\Service\AnnonceSettings;
use Annonce\Service\CguService;
use Annonce\Service\PhotoStorage;
use Application\Service\AuthService;
use Application\Util\ClientIp;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Entity\User;

/**
 * Espace Petites annonces, cote membres. Chaque page verifie l'acces
 * (AnnonceAccess) puis l'acceptation des CGU en vigueur.
 */
class AnnonceController extends AbstractActionController
{
    private const PER_PAGE = 12;

    private EntityManager $entityManager;
    private AuthService $authService;
    private AnnonceAccess $access;
    private AnnonceManager $annonceManager;
    private AnnonceSettings $settings;
    private CguService $cguService;
    private PhotoStorage $photoStorage;

    public function __construct(
        EntityManager $entityManager,
        AuthService $authService,
        AnnonceAccess $access,
        AnnonceManager $annonceManager,
        AnnonceSettings $settings,
        CguService $cguService,
        PhotoStorage $photoStorage
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->access = $access;
        $this->annonceManager = $annonceManager;
        $this->settings = $settings;
        $this->cguService = $cguService;
        $this->photoStorage = $photoStorage;
    }

    /**
     * Liste des annonces en ligne : recherche et filtre par catégorie.
     */
    public function indexAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $this->annonceManager->purgeIfDue();

        $page = max(1, InputSanitizer::cleanInt($this->params()->fromQuery('page', 1)));
        $search = mb_substr(InputSanitizer::cleanString($this->params()->fromQuery('q', '')), 0, 100);
        $tagId = InputSanitizer::cleanInt($this->params()->fromQuery('tag', 0)) ?: null;
        $annonces = $this->entityManager->getRepository(Annonce::class)->searchOnline($page, self::PER_PAGE, $search, $tagId);

        return $this->page('annonce/index', [
            'annonces' => $annonces,
            'page' => $page,
            'pages' => max(1, (int) ceil(count($annonces) / self::PER_PAGE)),
            'search' => $search,
            'tagId' => $tagId,
            'tags' => $this->annonceManager->activeTags(),
        ]);
    }

    /**
     * Fiche d'une annonce : en ligne pour tous, sinon pour son auteur et le GOD.
     */
    public function viewAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        $annonce = $this->findAnnonce(InputSanitizer::cleanInt($this->params()->fromRoute('id')));
        if (!$annonce || !$this->canSee($annonce, $user)) {
            $this->flashMessenger()->addErrorMessage("Cette annonce n'existe pas ou n'est plus en ligne.");
            return $this->redirect()->toRoute('annonces');
        }

        return $this->page('annonce/view', [
            'annonce' => $annonce,
            'isOwner' => $annonce->isOwnedBy($user),
            'canRenew' => $annonce->isOwnedBy($user) && $this->annonceManager->canRenew($annonce),
        ]);
    }

    /**
     * Annonces du membre : en ligne, expirees ou retirees (jusqu'a la purge).
     */
    public function mineAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        $annonces = $this->entityManager->getRepository(Annonce::class)->findByOwner($user);
        $renewable = [];
        foreach ($annonces as $annonce) {
            $renewable[$annonce->getId()] = $this->annonceManager->canRenew($annonce);
        }

        return $this->page('annonce/mine', [
            'annonces' => $annonces,
            'renewable' => $renewable,
            'onlineCount' => $this->annonceManager->countOnline($user),
            'maxOnline' => $this->settings->getInt(AnnonceSettings::MAX_ONLINE),
            'restriction' => $this->access->publishRestriction($user),
            'purgeDays' => $this->settings->getInt(AnnonceSettings::PURGE_DAYS),
        ]);
    }

    public function newAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        if ($restriction = $this->access->publishRestriction($user)) {
            $this->flashMessenger()->addErrorMessage($restriction);
            return $this->redirect()->toRoute('annonces-mine');
        }
        if ($this->annonceManager->hasReachedQuota($user)) {
            $this->flashMessenger()->addErrorMessage('Vous avez déjà le nombre maximum d’annonces en ligne. Retirez-en une pour en publier une nouvelle.');
            return $this->redirect()->toRoute('annonces-mine');
        }
        return $this->handleForm(null, $user);
    }

    public function editAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        $user = $this->currentUser();
        $annonce = $this->findAnnonce(InputSanitizer::cleanInt($this->params()->fromRoute('id')));
        if (!$annonce || !$annonce->isOwnedBy($user) || $annonce->isClosed()) {
            $this->flashMessenger()->addErrorMessage('Annonce introuvable.');
            return $this->redirect()->toRoute('annonces-mine');
        }
        return $this->handleForm($annonce, $user);
    }

    public function renewAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        if ($annonce = $this->findOwnedFromPost()) {
            $error = $this->annonceManager->renew($annonce);
            if ($error) {
                $this->flashMessenger()->addErrorMessage($error);
            } else {
                $this->flashMessenger()->addSuccessMessage('Annonce renouvelée jusqu’au ' . $annonce->getExpiresAt()->format('d/m/Y') . '.');
            }
        }
        return $this->redirect()->toRoute('annonces-mine');
    }

    public function withdrawAction()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }
        if ($annonce = $this->findOwnedFromPost()) {
            $this->annonceManager->withdraw($annonce);
            $this->flashMessenger()->addSuccessMessage('Annonce retirée.');
        }
        return $this->redirect()->toRoute('annonces-mine');
    }

    /**
     * Photo d'une annonce, servie depuis data/annonces/ aux seuls membres
     * qui peuvent voir l'annonce.
     */
    public function photoAction()
    {
        $user = $this->currentUser();
        $response = $this->getResponse();
        $photo = $this->entityManager->getRepository(AnnoncePhoto::class)->find(InputSanitizer::cleanInt($this->params()->fromRoute('id')));
        $path = null;
        if ($photo && $this->access->canAccess($user) && $this->canSee($photo->getAnnonce(), $user)) {
            $path = $this->photoStorage->path($photo->getAnnonce()->getId(), $photo->getFilename());
        }
        if ($path === null) {
            $response->setStatusCode(404);
            return $response;
        }
        $response->getHeaders()->addHeaders([
            'Content-Type' => 'image/jpeg',
            'Content-Length' => (string) filesize($path),
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setContent((string) file_get_contents($path));
        return $response;
    }

    /**
     * CGU en vigueur : lecture, et acceptation si ce n'est pas deja fait.
     */
    public function cguAction()
    {
        if ($redirect = $this->guard(false)) {
            return $redirect;
        }
        $user = $this->currentUser();
        $current = $this->cguService->current();
        if (!$current) {
            $this->flashMessenger()->addErrorMessage('Espace Petites annonces momentanément indisponible.');
            return $this->redirect()->toRoute($user->isGod() ? 'admin-annonces-cgu' : 'home');
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            if (!$request->getPost('accept')) {
                $this->flashMessenger()->addErrorMessage('Cochez la case pour accepter les conditions.');
                return $this->redirect()->toRoute('annonces-cgu');
            }
            $this->cguService->acceptCurrent($user, ClientIp::resolve($request->getServer()->toArray(), $this->trustedProxies()));
            $this->flashMessenger()->addSuccessMessage('Conditions acceptées. Bienvenue dans les Petites annonces !');
            return $this->redirect()->toRoute('annonces');
        }

        return $this->page('annonce/cgu', [
            'cgu' => $current,
            'accepted' => $this->cguService->hasAcceptedCurrent($user),
        ]);
    }

    /**
     * Formulaire de creation / modification.
     */
    private function handleForm(?Annonce $annonce, User $user)
    {
        $request = $this->getRequest();
        $values = $annonce ? [
            'title' => $annonce->getTitle(),
            'description' => $annonce->getDescription(),
            'price' => $annonce->isFree() ? '' : (string) $annonce->getPrice(),
            'free' => $annonce->isFree(),
            'tags' => array_map(fn (AnnonceTag $tag) => $tag->getId(), $annonce->getTags()->toArray()),
        ] : ['title' => '', 'description' => '', 'price' => '', 'free' => false, 'tags' => []];
        $errors = [];

        if ($request->isPost()) {
            $post = $request->getPost()->toArray();
            $files = $request->getFiles()->toArray();
            $result = $this->annonceManager->save($annonce, $user, $post, $this->normalizeUploads($files['photos'] ?? []));
            if (!$result['errors']) {
                $this->flashMessenger()->addSuccessMessage($result['annonce']->isPending()
                    ? 'Annonce envoyée : elle sera visible après validation par la modération.'
                    : ($annonce ? 'Annonce enregistrée.' : 'Annonce publiée !'));
                return $this->redirect()->toRoute('annonces-view', ['id' => $result['annonce']->getId()]);
            }
            $errors = $result['errors'];
            $values = [
                'title' => InputSanitizer::cleanString($post['title'] ?? ''),
                'description' => InputSanitizer::cleanText($post['description'] ?? ''),
                'price' => InputSanitizer::cleanString($post['price'] ?? ''),
                'free' => !empty($post['free']),
                'tags' => array_map('intval', (array) ($post['tags'] ?? [])),
            ];
        }

        // Tags proposes : les actifs, plus ceux deja poses sur l'annonce (desactives depuis)
        $tags = $this->annonceManager->activeTags();
        if ($annonce) {
            foreach ($annonce->getTags() as $tag) {
                if (!in_array($tag, $tags, true)) {
                    $tags[] = $tag;
                }
            }
        }

        return $this->page('annonce/form', [
            'annonce' => $annonce,
            'values' => $values,
            'errors' => $errors,
            'tags' => $tags,
        ]);
    }

    /**
     * Fichiers d'un champ multiple : liste d'entrees $_FILES, quelle que soit
     * la forme renvoyee (un seul fichier ou plusieurs).
     */
    private function normalizeUploads($files): array
    {
        if (!is_array($files)) {
            return [];
        }
        if (isset($files['tmp_name'])) {
            return [$files];
        }
        return array_values(array_filter($files, 'is_array'));
    }

    /**
     * Redirection si le compte n'a pas acces a l'espace ou n'a pas accepte
     * les CGU en vigueur ($requireCgu), sinon null.
     */
    private function guard(bool $requireCgu = true)
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirect()->toRoute('login');
        }
        if (!$this->access->canAccess($user)) {
            $this->flashMessenger()->addErrorMessage('Accès refusé.');
            return $this->redirect()->toRoute('home');
        }
        if ($requireCgu && !$this->cguService->hasAcceptedCurrent($user)) {
            return $this->redirect()->toRoute('annonces-cgu');
        }
        return null;
    }

    private function canSee(Annonce $annonce, ?User $user): bool
    {
        return $annonce->isOnline() || $annonce->isOwnedBy($user) || ($user && $user->canModerate());
    }

    private function findAnnonce(int $id): ?Annonce
    {
        return $id > 0 ? $this->entityManager->getRepository(Annonce::class)->find($id) : null;
    }

    /**
     * Annonce designee par le POST (id), si elle appartient au membre connecte.
     */
    private function findOwnedFromPost(): ?Annonce
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return null;
        }
        $annonce = $this->findAnnonce(InputSanitizer::cleanInt($request->getPost('id')));
        if (!$annonce || !$annonce->isOwnedBy($this->currentUser())) {
            $this->flashMessenger()->addErrorMessage('Annonce introuvable.');
            return null;
        }
        return $annonce;
    }

    private function currentUser(): ?User
    {
        $user = $this->authService->getIdentity();
        return $user instanceof User ? $user : null;
    }

    private function trustedProxies(): array
    {
        $config = $this->getEvent()->getApplication()->getServiceManager()->get('config');
        return $config['trusted_proxies'] ?? [];
    }

    private function page(string $template, array $variables): ViewModel
    {
        $this->layout()->setVariable('activeMenu', 'annonces');
        $view = new ViewModel($variables);
        $view->setTemplate($template);
        return $view;
    }
}
