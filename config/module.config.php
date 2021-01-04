<?php
namespace Adminer;

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\Admin\IndexController::class => Service\Controller\Admin\IndexControllerFactory::class,
        ],
    ],
    'navigation' => [
        'AdminGlobal' => [
            [
                'label' => 'Adminer', // @translate
                'route' => 'admin/adminer',
                'controller' => Controller\Admin\IndexController::class,
                'action' => 'index',
                // 'privilege' => 'browse',
                'class' => '.o-icon-settings',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'adminer' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/adminer/manager',
                            'defaults' => [
                                '__NAMESPACE__' => 'Adminer\Controller\Admin',
                                'controller' => 'IndexController',
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'adminer-mysql' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/adminer',
                            'defaults' => [
                                '__NAMESPACE__' => 'Adminer\Controller\Admin',
                                'controller' => 'IndexController',
                                'action' => 'adminerMysql',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
];
