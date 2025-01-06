<?php declare(strict_types=1);

namespace Adminer;

use Adminer\Form\ConfigForm;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;
use Omeka\Stdlib\Message;

/**
 * Adminer.
 *
 * @copyright Daniel Berthereau, 2019-2025
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        /** @var \Omeka\Permissions\Acl $acl */
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');

        $acl
            ->deny(
                null,
                [
                    \Adminer\Controller\Admin\IndexController::class,
                ]
            )
            ->allow(
                \Omeka\Permissions\Acl::ROLE_GLOBAL_ADMIN,
                [
                    \Adminer\Controller\Admin\IndexController::class,
                ]
            );
    }

    public function install(ServiceLocatorInterface $services): void
    {
        $plugins = $services->get('ControllerPluginManager');
        $translate = $plugins->get('translate');
        $messenger = $plugins->get('messenger');

        $filename = __DIR__ . '/asset/vendor/adminer/adminer-mysql.phtml';
        if (!file_exists($filename)) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module requires the dependencies to be installed. See %1$sreadme%2$s.'), // @translate
                '<a href="https://gitlab.com/Daniel-KM/Omeka-S-module-Adminer#installation" target="_blank" rel="noopener">', '</a>'
            );
            $message->setEscapeHtml(false);
            $messenger->addError($message);
            throw new \Omeka\Module\Exception\ModuleCannotInstallException($translate('The module cannot be installed.')); // @ŧranslate
        }
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services): void
    {
        $filepath = __DIR__ . '/data/scripts/upgrade.php';
        $this->setServiceLocator($services);
        require_once $filepath;
    }

    public function uninstall(ServiceLocatorInterface $services): void
    {
        $settings = $services->get('Omeka\Settings');
        $settings->delete('adminer_readonly_user');
        $settings->delete('adminer_readonly_password');
        $settings->delete('adminer_full_access');
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $services = $this->getServiceLocator();

        // For security, don't resend original password.
        $data = [
            'adminer_readonly_user' => $renderer->setting('adminer_readonly_user'),
            'adminer_readonly_password' => null,
            'adminer_full_access' => $renderer->setting('adminer_full_access', false),
        ];

        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->init();
        $form->setData($data);

        return '<p>'
            . $renderer->translate('A read only user is required to use the module.') // @translate
            . ' ' . $renderer->translate('The password is not resent for security reasons.') // @translate
            . ' ' . $renderer->translate('This user can be created automatically if the Omeka database user has such a right.') // @translate
            . ' ' . $renderer->translate('It is possible but not recommended to use the full-access user as the read-only user.') // @translate
            . '</p>'
            . $renderer->formCollection($form);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $services = $this->getServiceLocator();
        $form = $services->get('FormElementManager')->get(ConfigForm::class);

        $params = $controller->getRequest()->getPost();

        $form->init();
        $form->setData($params);
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        /** @var \Omeka\Settings\Settings $settings */
        $settings = $services->get('Omeka\Settings');

        $params = $form->getData();
        $params = [
            'adminer_readonly_user' => (string) ($params['adminer_readonly_user'] ?? ''),
            'adminer_readonly_password' => (string) ($params['adminer_readonly_password'] ?? ''),
            'adminer_full_access' => !empty($params['adminer_readonly_user']),
        ];

        $existingParams = [
            'adminer_readonly_user' => (string) $settings->get('adminer_readonly_user'),
            'adminer_readonly_password' => (string) $settings->get('adminer_readonly_password'),
            'adminer_full_access' => (bool) $settings->get('adminer_readonly_password'),
        ];

        // Keep original password if empty.
        if ($params['adminer_readonly_user']
            && empty($params['adminer_readonly_password'])
            && $existingParams['adminer_readonly_user'] === $params['adminer_readonly_user']
        ) {
            $params['adminer_readonly_password'] = $existingParams['adminer_readonly_password'];
        }

        $settings->set('adminer_readonly_user', $params['adminer_readonly_user']);
        $settings->set('adminer_readonly_password', $params['adminer_readonly_password']);
        $settings->set('adminer_full_access', $params['adminer_full_access']);

        // Try to create the read-only user only if new.
        if (!$params['adminer_readonly_user']
            || !$params['adminer_readonly_password']
            || (
                !empty($existingParams['adminer_readonly_user'])
                && $existingParams['adminer_readonly_user'] === $params['adminer_readonly_user']
            )
        ) {
            return true;
        }

        $this->createReadOnlyUser();
        return true;
    }

    /**
     * Creation of the read-only user if needed and if possible.
     */
    protected function createReadOnlyUser(): bool
    {
        /**
         * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
         * @var \Omeka\Settings\Settings $settings
         * @var \Doctrine\DBAL\Connection $connection
         * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
         */
        $services = $this->getServiceLocator();
        $plugins = $services->get('ControllerPluginManager');
        $settings = $services->get('Omeka\Settings');
        $connection = $services->get('Omeka\Connection');
        $messenger = $plugins->get('messenger');

        $host = $connection->getParams()['host'] ?? 'localhost';
        $database = $connection->getDatabase();

        // Username and password are quoted for all queries below.
        $usernameUnquoted = $settings->get('adminer_readonly_user');
        $username = $connection->quote($usernameUnquoted);
        $password = $connection->quote($settings->get('adminer_readonly_password'));

        // Check if the user exists.
        $sql = <<<SQL
SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = $username);
SQL;
        try {
            $result = $connection->fetchOne($sql);
        } catch (\Exception $e) {
            $messenger->addError(
                'The Omeka database user has no rights to check or create a user. Add it manually yourself if needed.' // @translate
            );
            return true;
        }

        // Check grants of the user.
        $hasUser = !empty($result);
        if ($hasUser) {
            $sql = <<<SQL
SHOW GRANTS FOR $username@'$host';
SQL;
            try {
                $result = $connection->fetchAllAssociative($sql);
            } catch (\Exception $e) {
                $messenger->addError(
                    'The Omeka database user has no rights to check grants of a user. Add it manually yourself if needed.' // @translate
                );
                return false;
            }

            foreach ($result as $value) {
                $value = reset($value);
                if (strpos($value, 'GRANT ALL PRIVILEGES ON *.*') !== false
                    || strpos($value, 'GRANT SELECT ON *.*') !== false
                    || strpos($value, "GRANT ALL PRIVILEGES ON `$database`.*") !== false
                    || strpos($value, "GRANT SELECT ON `$database`.*") !== false
                ) {
                    return true;
                }
            }
        } else {
            $sql = <<<SQL
CREATE USER $username@'$host' IDENTIFIED BY $password;
SQL;
            try {
                $connection->executeStatement($sql);
            } catch (\Exception $e) {
                $messenger->addError(new Message(
                    'An error occurred during the creation of the read-only user "%s".', // @translate
                    $usernameUnquoted
                ));
                return false;
            }
        }

        // Grant Select privilege to user.
        $sql = <<<SQL
GRANT SELECT ON `$database`.* TO $username@'$host';
GRANT SHOW VIEW `$database`.* TO $username@'$host';
SQL;
        try {
            $connection->executeStatement($sql);
            $messenger->addSuccess(new Message(
                'The read-only user "%s" has been created.', // @translate
                $usernameUnquoted
            ));
        } catch (\Exception $e) {
            $messenger->addError(new Message(
                'An error occurred during the creation of the read-only user "%s".', // @translate
                $usernameUnquoted
            ));
            return false;
        }

        try {
            $connection->executeStatement('FLUSH PRIVILEGES;');
        } catch (\Exception $e) {
            $messenger->addError(new Message(
                'An error occurred when flushing privileges for user "%s".', // @translate
                $usernameUnquoted
            ));
            return false;
        }

        return true;
    }
}
