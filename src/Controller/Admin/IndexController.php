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
        $databaseConfig = $this->getDatabaseConfig();
        $hasReadOnly = $databaseConfig['readonly_user_name'] !== '' && $databaseConfig['readonly_user_password'] !== '';
        $hasFullAccess = $hasReadOnly
            && $databaseConfig['full_user_name'] !== '' && $databaseConfig['full_user_password'] !== '';
        return new ViewModel([
            'hasReadOnly' => $hasReadOnly,
            'hasFullAccess' => $hasFullAccess,
        ]);
    }

    public function adminerMysqlAction()
    {
        return $this->adminer('adminer');
    }

    public function adminerEditorMysqlAction()
    {
        return $this->adminer('editor');
    }

    protected function getDatabaseConfig()
    {
        $defaultKeys = [
            'readonly_user_name' => '',
            'readonly_user_password' => '',
            'full_user_name' => '',
            'full_user_password' => '',
        ];

        // Load db config to use it to show message.
        $filepath = OMEKA_PATH . '/config/database-adminer.ini';
        $reader = new \Laminas\Config\Reader\Ini();
        $dbFileConfig = file_exists($filepath)
            ? $reader->fromFile($filepath)
            : $defaultKeys;

        return array_intersect_key($dbFileConfig, $defaultKeys) + $this->dbConfig;
    }

    protected function adminer($type)
    {
        static $isPosted;

        $databaseConfig = $this->getDatabaseConfig();
        $hasReadOnly = $databaseConfig['readonly_user_name'] !== '' && $databaseConfig['readonly_user_password'] !== '';
        $hasFullAccess = $hasReadOnly
            && $databaseConfig['full_user_name'] !== '' && $databaseConfig['full_user_password'] !== '';
        $params = $this->params()->fromQuery();
        $login = $params['login'] ?? null;

        if ($login) {
            if ($isPosted || count($params) > 1) {
                $_POST = [];
            } else {
                $login = $login === 'full' ? 'full' : 'readonly';
                if ($login === 'readonly' && !$hasReadOnly) {
                    $this->messenger()->addError('Read only user is not configured.'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'index'], true);
                }
                if ($login === 'full' && !$hasFullAccess) {
                    $this->messenger()->addError('Full access user or read only user are not configured.'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'index'], true);
                }

                $username = $login === 'full' ? $databaseConfig['full_user_name'] : $databaseConfig['readonly_user_name'];
                $_POST = [
                    'auth' => [
                        // Warning: The driver for "mysql" is called "server"!
                        'driver' => 'server',
                        'server' => $databaseConfig['server'],
                        'db' => $databaseConfig['db'],
                        'username' => $username,
                        'password' => $login === 'full' ? $databaseConfig['full_user_password'] : $databaseConfig['readonly_user_password'],
                        'permanent' => '1',
                    ],
                ];
                $_GET = [
                    // Only the username is checked against post in adminer.
                    'username' => $username,
                ];
            }
            $isPosted = true;
        }

        // Either this simple layout, either view with terminal template, that
        // requires an include.
        $this->layout()->setTemplate('adminer/admin/index/layout');
        return new ViewModel();
    }
}
