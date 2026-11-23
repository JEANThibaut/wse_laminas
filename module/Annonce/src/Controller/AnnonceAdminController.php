<?php
namespace Annonce\Controller;

use Annonce\Entity\AnnonceTag;
use Annonce\Service\AnnonceManager;
use Annonce\Service\AnnonceSettings;
use Annonce\Service\CguService;
use Application\Service\AuthService;
use Application\Util\InputSanitizer;
use Doctrine\ORM\EntityManager;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

/**
 * GOD MODE > Petites annonces : reglages, categories (tags) et CGU.
 */
class AnnonceAdminController extends AbstractActionController
{
    private EntityManager $entityManager;
    private AuthService $authService;
    private AnnonceSettings $settings;
    private AnnonceManager $annonceManager;
    private CguService $cguService;

    public function __construct(
        EntityManager $entityManager,
        AuthService $authService,
        AnnonceSettings $settings,
        AnnonceManager $annonceManager,
        CguService $cguService
    ) {
        $this->entityManager = $entityManager;
        $this->authService = $authService;
        $this->settings = $settings;
        $this->annonceManager = $annonceManager;
        $this->cguService = $cguService;
    }

    /**
     * Reglages (quota, durees...) et purge immediate.
     */
    public function settingsAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($request->getPost('purge')) {
                $count = $this->annonceManager->purge();
                $this->flashMessenger()->addSuccessMessage($count . ' annonce(s) supprimée(s) définitivement.');
                return $this->redirect()->toRoute('admin-annonces');
            }
            foreach (array_keys(AnnonceSettings::EDITABLE) as $key) {
                $value = $request->getPost($key);
                if ($value !== null && $value !== '') {
                    $this->settings->set($key, (string) InputSanitizer::cleanInt($value));
                }
            }
            $this->settings->set(AnnonceSettings::REQUIRE_VALIDATION, $request->getPost(AnnonceSettings::REQUIRE_VALIDATION) ? '1' : '0');
            $this->flashMessenger()->addSuccessMessage('Réglages enregistrés.');
            return $this->redirect()->toRoute('admin-annonces');
        }

        $values = [];
        foreach (array_keys(AnnonceSettings::EDITABLE) as $key) {
            $values[$key] = $this->settings->getInt($key);
        }
        return $this->page('annonce-admin/settings', [
            'values' => $values,
            'requireValidation' => $this->settings->requiresValidation(),
        ]);
    }

    /**
     * Categories : liste dans l'ordre d'affichage, et ajout.
     */
    public function tagsAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $label = $this->readLabel();
            if ($label === '') {
                $this->flashMessenger()->addErrorMessage('Le nom de la catégorie est obligatoire.');
            } else {
                $tags = $this->findTags();
                $last = end($tags);
                $this->entityManager->persist(new AnnonceTag($label, $last ? $last->getPosition() + 1 : 1));
                $this->entityManager->flush();
                $this->flashMessenger()->addSuccessMessage('Catégorie ajoutée.');
            }
            return $this->redirect()->toRoute('admin-annonces-tags');
        }

        return $this->page('annonce-admin/tags', [
            'tags' => $this->findTags(),
            'usage' => $this->tagUsage(),
        ]);
    }

    /**
     * Renommer, activer ou desactiver une categorie.
     */
    public function tagEditAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        $tag = $request->isPost() ? $this->entityManager->getRepository(AnnonceTag::class)->find(InputSanitizer::cleanInt($request->getPost('id'))) : null;
        if ($tag) {
            $label = $this->readLabel();
            if ($label !== '') {
                $tag->setLabel($label);
            }
            $tag->setActive((bool) $request->getPost('is_active'));
            $this->entityManager->flush();
            $this->flashMessenger()->addSuccessMessage('Catégorie enregistrée.');
        }
        return $this->redirect()->toRoute('admin-annonces-tags');
    }

    public function tagMoveAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $tags = $this->findTags();
            $id = InputSanitizer::cleanInt($request->getPost('id'));
            $offset = $request->getPost('direction') === 'up' ? -1 : 1;
            foreach ($tags as $index => $tag) {
                if ($tag->getId() === $id && isset($tags[$index + $offset])) {
                    [$tags[$index], $tags[$index + $offset]] = [$tags[$index + $offset], $tags[$index]];
                    break;
                }
            }
            foreach (array_values($tags) as $index => $tag) {
                $tag->setPosition($index + 1);
            }
            $this->entityManager->flush();
        }
        return $this->redirect()->toRoute('admin-annonces-tags');
    }

    /**
     * Suppression d'une categorie jamais utilisee ; utilisee, elle est
     * seulement desactivee (les annonces la gardent).
     */
    public function tagDeleteAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        $tag = $request->isPost() ? $this->entityManager->getRepository(AnnonceTag::class)->find(InputSanitizer::cleanInt($request->getPost('id'))) : null;
        if ($tag) {
            if (($this->tagUsage()[$tag->getId()] ?? 0) > 0) {
                $tag->setActive(false);
                $this->flashMessenger()->addWarningMessage('Catégorie utilisée par des annonces : désactivée au lieu d’être supprimée.');
            } else {
                $this->entityManager->remove($tag);
                $this->flashMessenger()->addSuccessMessage('Catégorie supprimée.');
            }
            $this->entityManager->flush();
        }
        return $this->redirect()->toRoute('admin-annonces-tags');
    }

    /**
     * CGU : historique des versions, acceptations, publication d'une nouvelle version.
     */
    public function cguAction()
    {
        if ($redirect = $this->requireGod()) {
            return $redirect;
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $content = trim(str_replace(["\r\n", "\r"], "\n", (string) $request->getPost('content', '')));
            if ($content === '') {
                $this->flashMessenger()->addErrorMessage('Le texte des conditions est obligatoire.');
            } elseif (!$request->getPost('confirm')) {
                $this->flashMessenger()->addErrorMessage('Cochez la confirmation : tous les utilisateurs devront accepter à nouveau.');
            } else {
                $version = $this->cguService->publish($content);
                $this->flashMessenger()->addSuccessMessage('Version ' . $version->getVersion() . ' publiée.');
            }
            return $this->redirect()->toRoute('admin-annonces-cgu');
        }

        return $this->page('annonce-admin/cgu', [
            'current' => $this->cguService->current(),
            'history' => $this->cguService->history(),
            'acceptances' => $this->cguService->latestAcceptances(),
        ]);
    }

    /**
     * @return AnnonceTag[] toutes les categories, actives ou non, dans l'ordre
     */
    private function findTags(): array
    {
        return $this->entityManager->getRepository(AnnonceTag::class)->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /**
     * @return array<int, int> nombre d'annonces par categorie (id => nombre)
     */
    private function tagUsage(): array
    {
        $rows = $this->entityManager->getConnection()
            ->executeQuery('SELECT tag_id, COUNT(*) AS total FROM annonce_tag_link GROUP BY tag_id')
            ->fetchAllAssociative();
        $usage = [];
        foreach ($rows as $row) {
            $usage[(int) $row['tag_id']] = (int) $row['total'];
        }
        return $usage;
    }

    private function readLabel(): string
    {
        return mb_substr(InputSanitizer::cleanString($this->getRequest()->getPost('label')), 0, AnnonceTag::LABEL_MAX);
    }

    private function requireGod()
    {
        $user = $this->authService->getIdentity();
        if ($user && $user->isGod()) {
            return null;
        }
        $this->flashMessenger()->addErrorMessage('Accès refusé.');
        return $this->redirect()->toRoute('home');
    }

    private function page(string $template, array $variables): ViewModel
    {
        $this->layout()->setVariable('activeMenu', 'admin-annonces');
        $view = new ViewModel($variables);
        $view->setTemplate($template);
        return $view;
    }
}
