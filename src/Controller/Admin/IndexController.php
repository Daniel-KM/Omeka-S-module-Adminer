<?php declare(strict_types=1);

namespace Adminer\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Stdlib\Message;

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
        $hasFakeReadOnly = $hasReadOnly && $hasFullAccess
            && $databaseConfig['readonly_user_name'] === $databaseConfig['full_user_name'];
        if ($hasFakeReadOnly) {
            $this->messenger()->addWarning(new Message(
                'Warning: the read-only user is the same than the full-access user.' // @translate
            ));
        }

        // Check for the presence of adminer to fix bad install/upgrade.
        $filename = dirname(__DIR__, 3) . '/asset/vendor/adminer/adminer-mysql.phtml';
        $hasDependencies = file_exists($filename);
        if (!$hasDependencies) {
            $message = new \Omeka\Stdlib\Message(
                $this->translate('The module requires the dependencies to be installed. See %1$sreadme%2$s.'), // @translate
                '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-Adminer#installation" rel="noopener">', '</a>'
            );
            $message->setEscapeHtml(false);
            $this->messenger()->addError($message);
        }

        return new ViewModel([
            'hasDependencies' => $hasDependencies,
            'hasReadOnly' => $hasReadOnly,
            'hasFullAccess' => $hasFullAccess,
            'hasFakeReadOnly' => $hasFakeReadOnly,
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

    protected function adminer(string $type)
    {
        // Avoid an infinite loop.
        static $isPosted;

        // Check for the presence of adminer to fix bad install/upgrade.
        $filename = dirname(__DIR__, 3) . '/asset/vendor/adminer/adminer-mysql.phtml';
        if (!file_exists($filename)) {
            throw new \RuntimeException(
                $this->translate('The module requires the dependencies to be installed. See readme.') // @translate
            );
        }

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

        // The view template is the called one.
        return new ViewModel();
    }

    protected function getDatabaseConfig(): array
    {
        $settings = $this->settings();
        $config = [
            'readonly_user_name' => (string) $settings->get('adminer_readonly_user', ''),
            'readonly_user_password' => (string) $settings->get('adminer_readonly_user', ''),
        ];
        return $config + $this->dbConfig;
    }
}
