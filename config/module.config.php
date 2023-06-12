<?php declare(strict_types=1);

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
        'AdminModule' => [
            [
                'label' => 'Database', // @translate
                'route' => 'admin/adminer',
                'controller' => Controller\Admin\IndexController::class,
                'action' => 'index',
                // 'privilege' => 'browse',
                'class' => 'o-icon- fa-database',
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
                    'adminer-editor-mysql' => [
                        'type' => \Laminas\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/adminer-editor',
                            'defaults' => [
                                '__NAMESPACE__' => 'Adminer\Controller\Admin',
                                'controller' => 'IndexController',
                                'action' => 'adminerEditorMysql',
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
    'adminer' => [
        'config' => [
            'adminer_readonly_user' => null,
            'adminer_readonly_password' => null,
            'adminer_full_access' => false,
        ],
    ],
];
