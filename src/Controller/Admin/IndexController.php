<?php declare(strict_types=1);

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
        $defaultKeys = [
            'default_user_name' => '',
            'default_user_password' => '',
            'main_user_name' => '',
            'main_user_password' => '',
        ];

        // Load db config to use it to show message.
        $filepath = OMEKA_PATH . '/config/database-adminer.ini';
        $reader = new \Laminas\Config\Reader\Ini();
        $dbFileConfig = file_exists($filepath)
            ? $reader->fromFile($filepath)
            : $defaultKeys;

        $dbFileConfig = array_intersect_key($dbFileConfig, $defaultKeys) + $this->dbConfig;

        return new ViewModel([
            'dbConfig' => $dbFileConfig,
        ]);
    }

    public function adminerMysqlAction()
    {
        // Either this simple layout, either view with terminal template, that
        // requires an include.
        $this->layout()->setTemplate('adminer/admin/index/layout');
        return new ViewModel();
    }
}
