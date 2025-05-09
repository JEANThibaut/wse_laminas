<?php
namespace Application\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class LoginForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('login');

        $this->add([
            'name' => 'email',
            'type' => Element\Email::class,
            'options' => [
                'label' => 'Email',
            ],
            'attributes' => [
                'required' => 'required',
                'placeholder' => 'Email',
            ],
        ]);

        $this->add([
            'name' => 'password',
            'type' => Element\Password::class,
            'options' => [
                'label' => 'Mot de passe',
            ],
            'attributes' => [
                'required' => 'required',
                'placeholder' => 'Mot de passe',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Se connecter',
                'class' => 'btn btn-primary',
            ],
        ]);
    }
}
