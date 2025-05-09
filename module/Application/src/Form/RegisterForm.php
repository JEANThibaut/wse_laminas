<?php

declare(strict_types=1);

namespace Application\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;
use Laminas\InputFilter\InputFilter;

class RegisterForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('register');

        $this->add([
            'name' => 'lastname',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Nom',
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);


        $this->add([
            'name' => 'firstname',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Prénom',
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        
        $this->add([
            'name' => 'nickname',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Pseudo',
            ],
            'attributes' => [
                'required' => false,
            ],
        ]);

        $this->add([
            'name' => 'birthday',
            'type' => Element\Date::class, 
            'options' => [
                'label' => 'Date de naissance',
                'format' => 'Y-m-d', 
            ],
            'attributes' => [
                'required' => true,
                'min' => '1900-01-01', 
                'max' => date('Y-m-d'), 
            ],
        ]);

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
            'name' => 'confirm_password',
            'type' => Element\Password::class,
            'options' => [
                'label' => 'Confirmer le mot de passe',
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'S\'inscrire',
            ],
        ]);

        // Ajouter la validation
        $this->setInputFilter($this->getInputFilterSpecification());
    }

    public function getInputFilterSpecification()
    {
        $inputFilter = new InputFilter();

        $inputFilter->add([
            'name' => 'firstname',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
        ]);
        $inputFilter->add([
            'name' => 'lastname',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
        ]);

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

        $inputFilter->add([
            'name' => 'confirm_password',
            'required' => true,
            'validators' => [
                [
                    'name' => 'Identical',
                    'options' => [
                        'token' => 'password',
                        'message' => 'Les mots de passe doivent être identiques',
                    ],
                ],
            ],
        ]);

        return $inputFilter;
    }
}
