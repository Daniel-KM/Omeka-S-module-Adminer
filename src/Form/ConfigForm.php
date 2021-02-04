<?php declare(strict_types=1);

namespace Adminer\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;

class ConfigForm extends Form
{
    public function init(): void
    {
        $this
            ->add([
                'type' => Element\Text::class,
                'name' => 'readonly_user_name',
                'options' => [
                    'label' => 'Read only user name', // @translate
                ],
            ])
            ->add([
                'type' => Element\Password::class,
                'name' => 'readonly_user_password',
                'options' => [
                    'label' => 'Read only user password', // @translate
                ],
            ])

            ->add([
                'type' => Element\Text::class,
                'name' => 'full_user_name',
                'options' => [
                    'label' => 'Full access user name', // @translate
                ],
            ])
            ->add([
                'type' => Element\Password::class,
                'name' => 'full_user_password',
                'options' => [
                    'label' => 'Full access user password', // @translate
                ],
            ]);
    }
}
