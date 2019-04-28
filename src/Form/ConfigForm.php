<?php
namespace Adminer\Form;

use Zend\Form\Element;
use Zend\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => Element\Text::class,
            'name' => 'default_user_name',
            'options' => [
                'label' => 'Read only user name', // @translate
            ],
        ]);
        $this->add([
            'type' => Element\Text::class,
            'name' => 'default_user_password',
            'options' => [
                'label' => 'Read only user password', // @translate
            ],
        ]);

        $this->add([
            'type' => Element\Text::class,
            'name' => 'main_user_name',
            'options' => [
                'label' => 'Full access user name', // @translate
            ],
        ]);
        $this->add([
            'type' => Element\Text::class,
            'name' => 'main_user_password',
            'options'   => [
                'label' => 'Full access user password', // @translate
            ],
        ]);
    }
}
