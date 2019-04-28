<?php

namespace Adminer\Form;

use Zend\Form\Element\Text;
use Zend\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => Text::class,
            'name' => 'db_name',
            'options' => [
                'label' => 'DB name',
            ]
        ]);
        $this->add([
            'type' => Element\Text::class,
            'name' => 'default_user_name',
            'options' => [
               'label' => 'Default user name',
            ]
        ]);
        $this->add([
            'type' => Text::class,
            'name' => 'default_user_password',
            'options' => [
                'label' => 'Default user password',
            ]
        ]);
        $this->add([
            'type' => Text::class,
            'name' => 'main_user_name',
            'options' => [
                'label' => 'Main user name',
            ]
        ]);
        $this->add([
            'type' => Text::class,
            'name' => 'main_user_password',
            'options'   => [
                'label' => 'Main user password',
            ]
        ]);
    }
}
