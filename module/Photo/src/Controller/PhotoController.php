<?php

namespace Photo\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;


class PhotoController extends AbstractActionController
{

    private $authService;
    private $entityManager;

    public function __construct($entityManager, $authService, )
    {
        $this->entityManager = $entityManager;
        $this->authService=$authService;
    }



    public function indexAction()

{


    if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
        $this->flashMessenger()->addErrorMessage('Accès refusé.');
        return $redirect;
    }

    $currentUser = $this->authService->getIdentity();


    $photoBaseDir = 'public/photos';
    $photoWebPath = '/photos';

    $photosByType = [];

    if (is_dir($photoBaseDir)) {
        foreach (scandir($photoBaseDir) as $typeFolder) {
            if ($typeFolder === '.' || $typeFolder === '..') {
                continue;
            }

            $typePath = $photoBaseDir . '/' . $typeFolder;
            if (!is_dir($typePath)) {
                continue;
            }

            $dates = [];

            foreach (scandir($typePath) as $dateFolder) {
                if ($dateFolder === '.' || $dateFolder === '..') {
                    continue;
                }

                $fullPath = $typePath . '/' . $dateFolder;
                if (is_dir($fullPath)) {
                    $files = array_filter(scandir($fullPath), function ($f) use ($fullPath) {
                        return preg_match('/\.(jpg|jpeg|png|gif)$/i', $f) && is_file($fullPath . '/' . $f);
                    });

                    if (!empty($files)) {
                        $dates[] = $dateFolder;
                    }
                }
            }

            if (!empty($dates)) {
                sort($dates);
                $photosByType[$typeFolder] = $dates;
            }
        }
    }
    $preferredOrder = ['games', 'actu'];

        // Trie le tableau $photosByType selon $preferredOrder
        uksort($photosByType, function ($a, $b) use ($preferredOrder) {
            $indexA = array_search($a, $preferredOrder);
            $indexB = array_search($b, $preferredOrder);

            $indexA = $indexA !== false ? $indexA : 999;
            $indexB = $indexB !== false ? $indexB : 999;

            return $indexA <=> $indexB ?: strcmp($a, $b); // fallback alphabetic if equal
        });

           $view = new ViewModel([
            'currentUser'   => $currentUser,
            'photosByType'  => $photosByType,
            'photoWebPath'  => $photoWebPath,
        ]);

        $this->layout()->setVariable('activeMenu', 'photo-index');
        $view->setTemplate('photo/photo-index');
        return $view;

}



public function photosViewAction(){

    if ($redirect = $this->authService->requireRoles(['admin'], $this->redirect())) {
        $this->flashMessenger()->addErrorMessage('Accès refusé.');
        return $redirect;
    }   
    // Désactive le pull-to-refresh pour cette page
    $this->layout()->setVariable('disablePtr', true);

    $currentUser = $this->authService->getIdentity();
    $date = $this->params()->fromRoute('date');
    $type = $this->params()->fromRoute('type');
    $images = [];

    $baseDir = 'public/photos/'.$type.'/'. $date;
    $webPath = '/photos/'.$type.'/' . $date;
    if ($date && is_dir($baseDir)) {
        foreach (scandir($baseDir) as $file) {
            if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $file)) {
                $images[] = $webPath . '/' . $file;
            }
        }
    }

    $view = new ViewModel([
        'currentUser' => $currentUser,
        'date' => $date,
        'images' => $images,
    ]);

    $this->layout()->setVariable('activeMenu', 'photo-index');
    $view->setTemplate('photo/photo-view'); 

    return $view;
}

    



}
