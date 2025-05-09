<?php
namespace Profil\Form;

use Laminas\Form\Form;
use Laminas\Form\Element;

class RepliqueForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct($name); // Appel important !

        $this->add([
            'name' => 'nom_replique',
            'type' => 'text',
            'options' => [
                'label' => 'Nom de la réplique',
            ],
            'attributes' => [
                'required' => true,
                'placeholder' => 'Nom de la réplique',
            ],
        ]);

        $this->add([
            'name' => 'type_replique',
            'type' => Element\Select::class,
            'options' => [
                'label' => 'Type de réplique',
                'value_options' => [
                    '' => '--- Choisir ---',
                    'AEG' => 'AEG',
                    'GBBR' => 'GBBR',
                    'Sniper' => 'Sniper',
                    'Pistolet' => 'Pistolet',
                    'Autre' => 'Autre',
                ],
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);
        
        $this->add([
            'name' => 'puissance',
            'type' => 'text',
            'options' => [
                'label' => 'Puissance (en Joules)',
            ],
            'attributes' => [
                'required' => true,
                'placeholder' => 'Puissance (en Joules)',
                'inputmode' => 'decimal', 
                'pattern' => '[0-9]*[.,]?[0-9]*',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Element\Submit::class,
            'attributes' => [
                'value' => 'Enregistrer',
                'class' => 'btn-primary',
            ],
        ]);
    }
}
