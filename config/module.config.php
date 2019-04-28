<?php

namespace Adminer;

use Zend\Router\Http\Literal;
use Zend\ServiceManager\Factory\InvokableFactory;

$reader = new \Zend\Config\Reader\Ini();
$db_config = $reader->fromFile(OMEKA_PATH . '/modules/Adminer/config/database.ini');

return [
    'controllers' => [
        'factories' => [
            Controller\Admin\IndexController::class => InvokableFactory::class,
        ]
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'index' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/adminer/index',
                            'defaults' => [
                                '__NAMESPACE__' => 'Adminer\Controller\Admin',
                                'controller' => 'IndexController',
                                'action' => 'index'
                            ]
                        ]
                    ],
                    'adminer' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/adminer',
                            'defaults' => [
                                '__NAMESPACE__' => 'Adminer\Controller\Admin',
                                'controller' => 'IndexController',
                                'action' => 'adminerMysql'
                            ]
                        ]
                    ],

                ]
            ],
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/Adminer/view'
        ]
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label'      => 'Adminer',
                'route'      => 'admin/index',
                'controller' => 'IndexController',
                'action'     => 'index',
                'pages' =>  [
                    ($db_config['default_user_name'] !== "" && $db_config['default_user_password'] !== "") ? [
                        'label'      => 'default (read only)',
                        'route'      => 'admin/adminer',
                        'controller' => 'access',
                        'action'     => 'browse',
                        'target'    => '_blank',
                        'query' => [
                            "server"    =>  "localhost",
                            "username"  =>  $db_config['default_user_name'],
                            "password"  =>  $db_config['default_user_password'],
                            "db"        =>  $db_config['db_name']
                        ],
                    ] : null,
                    ($db_config['main_user_name'] !== "" && $db_config['main_user_password'] !== "") ? [
                        'label'      => 'main (write access)',
                        'route'      => 'admin/adminer',
                        'controller' => 'access',
                        'action'     => 'browse',
                        'target'    => '_blank',
                        'query' => [
                            "server"    =>  "localhost",
                            "username"  =>  $db_config['main_user_name'],
                            "password"  =>  $db_config['main_user_password'],
                            "db"        =>  $db_config['db_name']
                        ]
                    ] : null
                ]
            ],
        ],
    ]
];
