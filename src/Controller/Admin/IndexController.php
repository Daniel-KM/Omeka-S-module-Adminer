<?php
namespace Adminer\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $defaultKeys = array_fill_keys([
            'db_name',
            'default_user_name',
            'default_user_password',
            'main_user_name',
            'main_user_password',
        ], '');

        // Load db config to use it to show message.
        $filepath = dirname(dirname(dirname(__DIR__))) . '/config/database-adminer.ini';
        $reader = new \Zend\Config\Reader\Ini();
        $dbConfig = file_exists($filepath)
            ? $reader->fromFile($filepath)
            : $defaultKeys;

        $dbConfig = array_intersect_key($dbConfig, $defaultKeys);

        $view = new ViewModel();
        $view->setVariable('db_config', $dbConfig);
        return $view;
    }

    public function adminerMysqlAction()
    {
        $this->layout()->setTemplate('layout/adminer/layout');
        return new ViewModel();
    }
}
