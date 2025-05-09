<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\InputFilter\InputFilter;

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
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'password',
            'type' => Element\Password::class,
            'options' => [
                'label' => 'Mot de passe',
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Se connecter',
            ],
        ]);

        $this->setInputFilter($this->getInputFilterSpecification());
    }

    public function getInputFilterSpecification()
    {
        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'email',
            'required' => true,
            'validators' => [
                ['name' => 'EmailAddress'],
            ],
        ]);

        $inputFilter->add([
            'name' => 'password',
            'required' => true,
        ]);

        return $inputFilter;
    }
}
