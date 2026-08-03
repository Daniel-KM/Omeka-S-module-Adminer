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

    /**
     * @var string
     */
    protected $locale;

    public function __construct(array $dbConfig, string $locale = '')
    {
        $this->dbConfig = $dbConfig;
        $this->locale = $locale;
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
                sprintf('<a href="%s">', htmlspecialchars($this->url()->fromRoute('admin/default', ['controller' => 'module', 'action' => 'configure'], ['query' => ['id' => 'Adminer']]))),
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
         * @var array
         */
        global $adminerAuthData;

        // Check for the presence of adminer to fix bad install/upgrade.
        $moduleDir = dirname(__DIR__, 3);
        if (!file_exists($moduleDir . '/asset/vendor/adminer/adminer-mysql.phtml')) {
            throw new \RuntimeException(
                $this->translate('The module requires the dependencies to be installed. See readme.') // @translate
            );
        }

        $databaseConfig = $this->getDatabaseConfig();
        $hasReadOnly = $databaseConfig['readonly_user_name'] !== '' && $databaseConfig['readonly_user_password'] !== '';
        $hasFullAccess = $databaseConfig['full_user_name'] !== '' && $databaseConfig['full_user_password'] !== '';

        // Use session-saved login type for clean url refreshes.
        $login = $this->params()->fromQuery('login');
        if ($login) {
            $loginIsFull = $login !== 'readonly';
            $_SESSION['adminer_omeka_login_type'] = $loginIsFull ? 'full' : 'readonly';
        } else {
            $loginIsFull = ($_SESSION['adminer_omeka_login_type'] ?? 'full') === 'full';
        }

        if ($loginIsFull && !$hasFullAccess) {
            $this->messenger()->addError('Full access user or read only user are not configured.'); // @translate
            return $this->redirect()->toRoute(null, ['action' => 'index'], true);
        } elseif (!$loginIsFull && !$hasReadOnly) {
            $this->messenger()->addError('Read only user is not configured.'); // @translate
            return $this->redirect()->toRoute(null, ['action' => 'index'], true);
        }

        $adminerAuthData = [
            // Warning: The driver for "mysql" is called "server"!
            'driver' => 'server',
            'server' => $databaseConfig['server'],
            'db' => $databaseConfig['db'],
            'username' => $loginIsFull ? $databaseConfig['full_user_name'] : $databaseConfig['readonly_user_name'],
            'password' => $loginIsFull ? $databaseConfig['full_user_password'] : $databaseConfig['readonly_user_password'],
            'ssl' => $databaseConfig['ssl'] ?? [],
            'installation_title' => (string) $this->settings()->get('installation_title', ''),
        ];

        // Log in without any form: Adminer reads the credentials from the
        // session, that is shared with Omeka, so they are set directly. So
        // there is no login page, no permanent login key and no csrf token to
        // forge: Adminer manages its own token for its own forms.
        // @see Adminer get_password() and get_session().
        $this->authenticate($adminerAuthData);

        $this->prepareLocale();

        // The default cannot be "asset/vendor/adminer/adminer.css", because it
        // is not in the list of designs.
        if (!array_key_exists('design', $_SESSION)) {
            $_SESSION['design'] = '../modules/Adminer/asset/vendor/adminer/designs/hever/adminer.css';
        }

        // Fix strict type issue.
        $_SESSION['translations'] ??= [];

        // Don't display warnings for adminer, that are managed outside of
        // Omeka.
        // TODO There is a double session issue:
        // PHP Warning: session_start(): Cannot send session cache limiter -
        // headers already sent.
        ini_set('display_errors', '0');

        // Url cleanup (stripping server/username/db from links) is handled by
        // the AdminerCleanUrls plugin via output buffering and JavaScript.

        // Register the plugins before including Adminer: the released files are
        // used as is, so the only extension point is adminer_object().
        require_once $moduleDir . '/src/adminer-object.php';

        require_once $type === 'editor'
            ? $moduleDir . '/asset/vendor/adminer/editor-mysql.phtml'
            : $moduleDir . '/asset/vendor/adminer/adminer-mysql.phtml';

        // Remove error reporting, because adminer enable it.
        // error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
        error_reporting(0);

        return (new ViewModel())
            ->setTerminal(true);
    }

    /**
     * Set the Adminer session and request keys used to connect to the database.
     *
     * @see vendor/vrana/adminer/adminer/include/auth.inc.php
     */
    protected function authenticate(array $authData): void
    {
        $driver = $authData['driver'];
        $server = $authData['server'];
        $username = $authData['username'];

        // The password is stored as a plain string: Adminer encrypts it only
        // when it stores it itself and it decrypts it only when it is an array.
        $_SESSION['pwds'][$driver][$server][$username] = $authData['password'];
        $_SESSION['db'][$driver][$server][$username][$authData['db']] = true;

        // Inject Adminer routing params so the url can be kept clean.
        $_GET += [
            $driver => $server,
            'username' => $username,
            'db' => $authData['db'],
        ];
    }

    /**
     * Use the locale of the current Omeka user as the language of Adminer.
     *
     * Adminer displays a language selector on each page, so its own choice is
     * kept: the Omeka locale is only pushed when it changes.
     *
     * Adminer checks the cookie first, then the session, and it skips any
     * unknown language, so the region variant is set as the cookie and the
     * plain language as the session: "pt_BR" uses "pt-br", but "fr_FR", that
     * has no region variant in Adminer, falls back to "fr". This avoids
     * duplicating the list of the languages of Adminer.
     *
     * @see vendor/vrana/adminer/adminer/include/lang.inc.php
     */
    protected function prepareLocale(): void
    {
        if ($this->locale === ''
            || ($_SESSION['adminer_omeka_locale'] ?? null) === $this->locale
        ) {
            return;
        }

        $_SESSION['adminer_omeka_locale'] = $this->locale;

        $language = strtolower(strtr($this->locale, ['_' => '-']));
        $_COOKIE['adminer_lang'] = $language;
        $_SESSION['lang'] = strtok($language, '-');
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

}
