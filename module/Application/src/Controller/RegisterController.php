<?php

declare(strict_types=1);

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Application\Form\RegisterForm;
use Laminas\Http\Request;

class RegisterController extends AbstractActionController
{
    public function registerAction()
    {
        $form = new RegisterForm();
        $request = $this->getRequest();

        if ($request instanceof Request && $request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();
                dump($data);
            }
        }
        
        
        return new ViewModel([
            'form' => $form,
        ]);
    }
}
