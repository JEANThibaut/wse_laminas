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


    // public function indexAction()
    // {

    //     $currentUser = $this->authService->getIdentity();
    //     $view = new ViewModel([
    //         'currentUser'=>$currentUser,
    //     ]);
    //     $this->layout()->setVariable('activeMenu', 'photos');
    //     $view->setTemplate('photo/photo-index');
    //     return $view;

    // }


    public function indexAction()
{
    $currentUser = $this->authService->getIdentity();

    $photoBaseDir = 'public/photos'; // chemin relatif depuis la racine du projet
    $photoWebPath = '/photos'; // pour les URLs

    $dates = [];

    if (is_dir($photoBaseDir)) {
        foreach (scandir($photoBaseDir) as $folder) {
            if ($folder === '.' || $folder === '..') {
                continue;
            }

            $fullPath = $photoBaseDir . '/' . $folder;
            if (is_dir($fullPath)) {
                $files = array_filter(scandir($fullPath), function ($f) use ($fullPath) {
                    return preg_match('/\.(jpg|jpeg|png|gif)$/i', $f) && is_file($fullPath . '/' . $f);
                });
                if (!empty($files)) {
                    $dates[] = $folder;
                }
            }
        }

        sort($dates);
    }

    $view = new ViewModel([
        'currentUser' => $currentUser,
        'dates'       => $dates,
        'photoWebPath' => $photoWebPath,
    ]);

    $this->layout()->setVariable('activeMenu', 'photo-index');
    $view->setTemplate('photo/photo-index');
    return $view;
}



public function photosViewAction()
{
    $currentUser = $this->authService->getIdentity();
    $date = $this->params()->fromRoute('date');
    $images = [];

    $baseDir = 'public/photos/' . $date;
    $webPath = '/photos/' . $date;
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
