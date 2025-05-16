<?php
namespace Game\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class GameForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        // Champ date
        $this->add([
            'type' => Element\Date::class,
            'options' => [
                'label' => 'Date',
            ],
            'attributes' => [
                'required' => true,
                'placeholder' => 'Date de la partie',
            ],
        ]);

        $this->add([
            'name' => 'player_max',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Joueurs maximum',
            ],
            'attributes' => [
                'required' => true,
                'min' => 1,
                'step' => 1,
                'placeholder' => 'Nombre max de joueurs',
                'inputmode' => 'numeric',
            ],
        ]);

        $this->add([
            'name' => 'status',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Statut',
                'value_options' => [
                    1 => 'Actif',
                    0 => 'Inactif',
                ],
            ],
            'attributes' => [
                'required' => true,
                'value' => 1,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Enregistrer',
                'class' => 'btn btn-primary',
            ],
        ]);
    }
}
