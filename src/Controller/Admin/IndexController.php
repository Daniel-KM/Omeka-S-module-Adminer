<?php

namespace Adminer\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel();

        // load db config to use it to show message
        $reader = new \Zend\Config\Reader\Ini();
        $db_config = $reader->fromFile(OMEKA_PATH . "/modules/Adminer/config/database.ini");

        $view->setVariable('db_config', $db_config);
        return $view;
    }

    public function adminerMysqlAction()
    {
        $this->layout()->setTemplate('layout/adminer/layout');
        return new ViewModel();
    }
}