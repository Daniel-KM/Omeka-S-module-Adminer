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
                'name' => 'adminer_readonly_user',
                'options' => [
                    'label' => 'Read only user name', // @translate
                ],
            ])
            ->add([
                'type' => Element\Password::class,
                'name' => 'adminer_readonly_password',
                'options' => [
                    'label' => 'Read only user password', // @translate
                ],
            ])
            ->add([
                'type' => Element\Checkbox::class,
                'name' => 'adminer_full_access',
                'options' => [
                    'label' => 'Allow full access via omeka credentials (not recommended)', // @translate
                ],
            ]);
    }
}
