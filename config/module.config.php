<?php

namespace Adminer;

use Zend\ServiceManager\Factory\InvokableFactory;

$reader = new \Zend\Config\Reader\Ini();
$db_config = $reader->fromFile(OMEKA_PATH . '/modules/Adminer/config/database.ini');

return [
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\Admin\IndexController::class => InvokableFactory::class,
        ],
    ],
    'navigation' => [
        'AdminGlobal' => [
            [
                'label'      => 'Adminer', // @translate
                'route'      => 'admin/index',
                'controller' => 'IndexController',
                'action'     => 'index',
                'pages' =>  [
                    ($db_config['default_user_name'] !== '' && $db_config['default_user_password'] !== '')
                    ? [
                        'label' => 'Read only', // @translate
                        'route' => 'admin/adminer',
                        'controller' => 'access',
                        'action' => 'browse',
                        'target' => '_blank',
                        'query' => [
                            'server' => 'localhost',
                            'username' => $db_config['default_user_name'],
                            'password' => $db_config['default_user_password'],
                            'db' => $db_config['db_name'],
                        ],
                    ]
                    : null,
                    ($db_config['main_user_name'] !== '' && $db_config['main_user_password'] !== '')
                    ? [
                        'label' => 'Full access', // @translate
                        'route' => 'admin/adminer',
                        'controller' => 'access',
                        'action' => 'browse',
                        'target' => '_blank',
                        'query' => [
                            'server' => 'localhost',
                            'username' => $db_config['main_user_name'],
                            'password' => $db_config['main_user_password'],
                            'db' => $db_config['db_name'],
                        ],
                    ]
                    : null
                ],
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'index' => [
                        'type' => \Zend\Router\Http\Literal::class,
                        'options' => [
                            'route' => '/adminer/index',
                            'defaults' => [
                                '__NAMESPACE__' => 'Adminer\Controller\Admin',
                                'controller' => 'IndexController',
                                'action' => 'index',
                            ],
                        ],
                    ],
                    'adminer' => [
                        'type' => \Zend\Router\Http\Literal::class,
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
