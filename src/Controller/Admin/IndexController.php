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
        /**
         * Used in required files.
         *
         * @var array $adminerAuthData
         */
        global $adminerAuthData;

        // Avoid an infinite loop. It is still needed when there is an issue
        // with the permanent key.
        static $isPosted;

        // Check for the presence of adminer to fix bad install/upgrade.
        $filename = dirname(__DIR__, 3) . '/asset/vendor/adminer/adminer-mysql.phtml';
        if (!file_exists($filename)) {
            throw new \RuntimeException(
                $this->translate('The module requires the dependencies to be installed. See readme.') // @translate
            );
        }

        $adminerAuthData = [];

        $databaseConfig = $this->getDatabaseConfig();
        $hasReadOnly = $databaseConfig['readonly_user_name'] !== '' && $databaseConfig['readonly_user_password'] !== '';
        $hasFullAccess = $hasReadOnly
            && $databaseConfig['full_user_name'] !== '' && $databaseConfig['full_user_password'] !== '';
        $params = $this->params()->fromQuery();
        $login = $params['login'] ?? null;
        // By default, on first load, use full login to avoid issue.
        $loginIsFull = $login !== 'readonly';

        $username = $loginIsFull ? $databaseConfig['full_user_name'] : $databaseConfig['readonly_user_name'];
        $authData = [
            // Warning: The driver for "mysql" is called "server"!
            'driver' => 'server',
            'server' => $databaseConfig['server'],
            'db' => $databaseConfig['db'],
            'username' => $username,
            'password' => $loginIsFull ? $databaseConfig['full_user_password'] : $databaseConfig['readonly_user_password'],
            'permanent' => '1',
        ];

        // This token may avoid issue with auth.
        // FIXME The token doesn't fix the first load.
        $this->prepareSessionToken();
        $token = $this->getToken();

        if ($login) {
            if ($isPosted || count($params) > 1) {
                // Avoid issue in vendor/adminerevo/adminerevo/adminer/include/auth.inc.php.
                $_POST = [
                    'token' => $token,
                ];
            } else {
                if (!$loginIsFull && !$hasReadOnly) {
                    $this->messenger()->addError('Read only user is not configured.'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'index'], true);
                }
                if ($loginIsFull && !$hasFullAccess) {
                    $this->messenger()->addError('Full access user or read only user are not configured.'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'index'], true);
                }
                $_POST = [
                    'auth' => $authData,
                    'token' => $token,
                ];
                $_GET = [
                    // Only the username is checked against post in adminer.
                    'username' => $authData['username'],
                ];
            }
            $isPosted = true;
        }

        $adminerKey = $this->initAdminerKey();

        $adminerAuthData = $authData;
        $adminerAuthData['adminer_key'] = $adminerKey;

        // Include the AdminerPlugin directly instead of autoload.
        include_once dirname(__DIR__, 2) . '/AdminerOmeka.php';

        // Don't display warnings for adminer, that are managed outside of Omeka.
        ini_set('display_errors', '0');

        // Once is required, because adminer load some files with the same url (favicon.ico, jush.js, functions.js, default.css).
        require_once dirname(__DIR__, 3) . '/view/adminer/admin/index/adminer-functions.phtml';
        require_once $type === 'editor'
            ? dirname(__DIR__, 3) . '/asset/vendor/adminer/editor-mysql.phtml'
            : dirname(__DIR__, 3) . '/asset/vendor/adminer/adminer-mysql.phtml';

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

    /**
     * Init the permanent adminer key.
     *
     * Adapted from Adminer functions password_file() and rand_string().
     * @see vendor/adminerevo/adminerevo/adminer/include/functions.inc.php
     */
    protected function initAdminerKey(): ?string
    {
        $filename = $this->getTempDir() . "/adminer.key";

        $code = null;

        if (file_exists($filename)) {
            $code = file_get_contents($filename);
            if (!$code) {
                @unlink($filename);
            }
        }

        if (!file_exists($filename)) {
            // Can have insufficient rights. Is not atomic.
            $fp = @fopen($filename, 'w');
            if ($fp) {
                chmod($filename, 0660);
                // 32 hexadecimal characters string.
                $code = md5(uniqid((string) mt_rand(), true));
                fwrite($fp, $code);
                fclose($fp);
            }
        }

        return $code ?: null;
    }

    /**
     * Get path of the temporary directory.
     *
     * Adapted from adminer function get_temp_dir()
     * @see vendor/adminerevo/adminerevo/adminer/include/functions.inc.php
     */
    protected function getTempDir(): string
    {
        // session_save_path() may contain other storage path.
        $return = ini_get('upload_tmp_dir');
        if (!$return) {
            if (function_exists('sys_get_temp_dir')) {
                $return = sys_get_temp_dir();
            } else {
                // Temp directory can be disabled by open_basedir.
                $filename = @tempnam('', '');
                if (!$filename) {
                    return false;
                }
                $return = dirname($filename);
                unlink($filename);
            }
        }
        return $return;
    }

    /**
     * Get of set the session token.
     *
     * @see vendor/adminerevo/adminerevo/adminer/include/auth.inc.php
     */
    protected function prepareSessionToken(): int
    {
        $token = empty($_SESSION['token']) ? null : (int) $_SESSION['token'];
        if (!$token) {
            // Defense against cross-site request forgery.
            $token = random_int(1, 1000000);
            $_SESSION['token'] = $token;
        }
        return $token;
    }

    /**
     * Generate BREACH resistant CSRF token.
     *
     * Adapted from adminer function get_token().
     * @see vendor/adminerevo/adminerevo/adminer/include/functions.inc.php
     */
    protected function getToken(): string
    {
        $rand = random_int(1, 1000000);
        return ($rand ^ ($_SESSION['token'] ?? '')) . ":$rand";
    }

    /**
     * Verify if supplied CSRF token is valid.
     *
     * Adapted from adminer function verify_token()..
     * @see vendor/adminerevo/adminerevo/adminer/include/functions.inc.php
     */
    protected function verifyToken(): bool
    {
        [$token, $rand] = explode(':', $_POST['token'] ?? '');
        return ($rand ^ ($_SESSION['token'] ?? '')) === $token;
    }
}
