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
        $hasFullAccess = $databaseConfig['full_user_name'] !== '' && $databaseConfig['full_user_password'] !== '';
        $hasFakeReadOnly = $hasReadOnly && $hasFullAccess
            && $databaseConfig['readonly_user_name'] === $databaseConfig['full_user_name'];
        if ($hasFakeReadOnly) {
            $this->messenger()->addWarning(new Message(
                'Warning: the read-only user is the same than the full-access user.' // @translate
            ));
        } elseif (!$hasReadOnly) {
            $this->messenger()->addWarning(new Message(
                'Warning: there is no read-only user. Use at your own risk!' // @translate
            ));
        }
        if (!$hasReadOnly && !$hasFullAccess) {
            $message = new Message(
                'Warning: no user are defined to access to the database. Check the %1$sconfig%2$s.', // @translate
                sprintf('<a href="%s">', $this->url()->fromRoute('admin/default', ['controller' => 'module', 'action' => 'configure'], ['query' => ['id' => 'Adminer']])),
                '</a>'
            );
            $message->setEscapeHtml(false);
            $this->messenger()->addWarning($message);
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
        $hasFullAccess = $databaseConfig['full_user_name'] !== '' && $databaseConfig['full_user_password'] !== '';
        $params = $this->params()->fromQuery();
        $login = $params['login'] ?? null;
        // Use session-saved login type for clean url refreshes.
        if ($login) {
            $loginIsFull = $login !== 'readonly';
            $_SESSION['adminer_omeka_login_type'] = $loginIsFull ? 'full' : 'readonly';
        } else {
            $loginIsFull = ($_SESSION['adminer_omeka_login_type'] ?? 'full') === 'full';
        }

        $username = $loginIsFull ? $databaseConfig['full_user_name'] : $databaseConfig['readonly_user_name'];
        $authData = [
            // Warning: The driver for "mysql" is called "server"!
            'driver' => 'server',
            'server' => $databaseConfig['server'],
            'db' => $databaseConfig['db'],
            'username' => $username,
            'password' => $loginIsFull ? $databaseConfig['full_user_password'] : $databaseConfig['readonly_user_password'],
            'permanent' => '1',
            'ssl' => $databaseConfig['ssl'] ?? [],
        ];

        // This token may avoid issue with auth.
        // FIXME The token doesn't fix the first load.
        $this->prepareSessionToken();
        $token = $this->getToken();

        if ($login) {
            // Preserve Adminer plugin POST data (e.g. design selection).
            $pluginPost = array_diff_key($_POST, ['auth' => 1, 'token' => 1]);
            if ($isPosted || count($params) > 1) {
                // Avoid issue in vendor/vrana/adminer/adminer/include/auth.inc.php.
                $_POST = [
                    'token' => $token,
                ] + $pluginPost;
            } else {
                if (!$loginIsFull && !$hasReadOnly) {
                    $this->messenger()->addError('Read only user is not configured.'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'index'], true);
                } elseif ($loginIsFull && !$hasFullAccess) {
                    $this->messenger()->addError('Full access user or read only user are not configured.'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'index'], true);
                }
                $_POST = [
                    'auth' => $authData,
                    'token' => $token,
                ] + $pluginPost;
                $_GET = [
                    // Only the username is checked against post in adminer.
                    'username' => $authData['username'],
                ];
            }
            $isPosted = true;
        }

        $adminerAuthData = $authData;

        $filename = 'adminer.key';
        $adminerAuthData['adminer_key'] = $this->initAdminerKey($filename);
        $adminerAuthData['installation_title'] = (string) $this->settings()->get('installation_title', '');

        // The default cannot be "asset/vendor/adminer/adminer.css", because it
        // is not in the list of designs.
        if (!array_key_exists('design', $_SESSION)) {
            $_SESSION['design'] = '../modules/Adminer/asset/vendor/adminer/designs/hever/adminer.css';
        }

        // Fix strict type issue.
        $_SESSION['translations'] ??= [];

        // Inject Adminer routing params so the url can be kept clean.
        $_GET += [
            'server' => $authData['server'],
            'username' => $authData['username'],
            'db' => $authData['db'],
        ];

        // Don't display warnings for adminer, that are managed outside of Omeka.
        // TODO There is a double session issue:
        // PHP Warning:  session_start(): Cannot send session cache limiter - headers already sent.
        ini_set('display_errors', '0');

        // Url cleanup (stripping server/username/db from links) is handled by
        // the AdminerCleanUrls plugin via output buffering and JavaScript.

        require_once $type === 'editor'
            ? dirname(__DIR__, 3) . '/asset/vendor/adminer/editor-mysql.phtml'
            : dirname(__DIR__, 3) . '/asset/vendor/adminer/adminer-mysql.phtml';

        // Remove error reporting, because adminer enable it.
        // error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
        error_reporting(0);

        return (new ViewModel())
            ->setTerminal(true);
    }

    protected function getDatabaseConfig(): array
    {
        $settings = $this->settings();
        $config = [
            'readonly_user_name' => (string) $settings->get('adminer_readonly_user', ''),
            'readonly_user_password' => (string) $settings->get('adminer_readonly_password', ''),
        ];
        return $config + $this->dbConfig;
    }

    /**
     * Init the permanent adminer key.
     *
     * Adapted from Adminer functions password_file() and rand_string().
     * @see vendor/vrana/adminer/adminer/include/functions.inc.php
     */
    protected function initAdminerKey(string $filename): ?string
    {
        $filename = $this->getTempDir() . '/' . $filename;

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
                $code = bin2hex(random_bytes(16));
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
     * @see vendor/vrana/adminer/adminer/include/functions.inc.php
     */
    protected function getTempDir(): string
    {
        return ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
    }

    /**
     * Get or set the session token.
     *
     * @see vendor/vrana/adminer/adminer/include/auth.inc.php
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
     * @see vendor/vrana/adminer/adminer/include/functions.inc.php
     */
    protected function getToken(): string
    {
        $rand = random_int(1, 1000000);
        return ($rand ^ ($_SESSION['token'] ?? '')) . ":$rand";
    }

    /**
     * Verify if supplied CSRF token is valid.
     *
     * Adapted from adminer function verify_token().
     * @see vendor/vrana/adminer/adminer/include/functions.inc.php
     *
     * @todo Currently unused. Remove or integrate into the auth flow.
     */
    protected function verifyToken(): bool
    {
        [$token, $rand] = explode(':', $_POST['token'] ?? ':');
        return ($rand ^ ($_SESSION['token'] ?? 0)) === (int) $token;
    }
}
