<?php
namespace Adminer\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var array
     */
    protected $dbConfig;

    public function __construct(array $dbConfig)
    {
        $this->dbConfig = $dbConfig;
    }

    public function indexAction()
    {
        $defaultKeys = array_fill_keys([
            'default_user_name',
            'default_user_password',
            'main_user_name',
            'main_user_password',
        ], '');

        // Load db config to use it to show message.
        $filepath = dirname(dirname(dirname(__DIR__))) . '/config/database-adminer.ini';
        $reader = new \Laminas\Config\Reader\Ini();
        $dbConfig = file_exists($filepath)
            ? $reader->fromFile($filepath)
            : $defaultKeys;

        $dbConfig = array_intersect_key($dbConfig, $defaultKeys) + $this->dbConfig;

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
