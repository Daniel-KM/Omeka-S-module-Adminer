<?php declare(strict_types=1);

namespace Adminer;

use Adminer\Form\ConfigForm;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Stdlib\Message;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $serviceLocator): void
    {
        $filepath = OMEKA_PATH . '/config/database-adminer.ini';
        if (file_exists($filepath)) {
            $result = is_writeable($filepath);
        } else {
            $result = @file_put_contents($filepath, '');
        }
        if ($result === false) {
            $message = new Message(
                'The file "config/database-adminer.ini" should be writeable to install the module.' // @translate
            );
            $messenger = new Messenger();
            $messenger->addWarning($message); // @translate
            throw new ModuleCannotInstallException((string) $message);
        }
        @chmod($filepath, 0770);
    }

    // Acl are not updated, so only admins can use the module.

    public function getConfigForm(PhpRenderer $renderer)
    {
        $filepath = OMEKA_PATH . '/config/database-adminer.ini';
        if (!$this->isWriteableFile($filepath)) {
            $messenger = new Messenger();
            $messenger->addWarning('The file config/database-adminer.ini is not writeable, so credentials cannot be updated.'); // @translate
            return '';
        }

        $reader = new \Laminas\Config\Reader\Ini();
        $dbConfig = file_exists($filepath)
            ? $reader->fromFile($filepath)
            : [];

        // For security, don't resend original password.
        $dbConfig['readonly_user_password'] = '';
        $dbConfig['full_user_password'] = '';

        $services = $this->getServiceLocator();
        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->init();
        $form->setData($dbConfig);

        return '<p>'
            . $renderer->translate('A read only user is required to use the module.') // @translate
            . ' ' . $renderer->translate('This user can be created automatically if the Omeka database user has such a right.') // @translate
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

        $params = $form->getData();
        $filepath = OMEKA_PATH . '/config/database-adminer.ini';

        if (!file_exists($filepath)) {
            $result = @file_put_contents($filepath, '');
            if ($result === false) {
                $controller->messenger()->addError('The file config/database-adminer.ini is not writeable, so credentials cannot be updated.'); // @translate
                return false;
            }
        } elseif (!$this->isWriteableFile($filepath)) {
            $controller->messenger()->addError('The file config/database-adminer.ini is not writeable, so credentials cannot be updated.'); // @translate
            return false;
        }

        $defaultParams = [
            'readonly_user_name' => '',
            'readonly_user_password' => '',
            'full_user_name' => '',
            'full_user_password' => '',
        ];
        $params = array_intersect_key($params, $defaultParams);

        $reader = new \Laminas\Config\Reader\Ini();
        $existingParams = $reader->fromFile($filepath) + $defaultParams;

        // Keep original password if empty.
        if ($params['readonly_user_name']
            && empty($params['readonly_user_password'])
            && $existingParams['readonly_user_name'] === $params['readonly_user_name']
        ) {
            $params['readonly_user_password'] = $existingParams['readonly_user_password'];
        }
        if ($params['full_user_name']
            && empty($params['full_user_password'])
            && $existingParams['full_user_name'] === $params['full_user_name']
        ) {
            $params['full_user_password'] = $existingParams['full_user_password'];
        }

        $writer = new \Laminas\Config\Writer\Ini();
        $writer->toFile($filepath, $params);
        @chmod($filepath, 0770);

        // Try to create the read-only user only if new.
        if (!$params['readonly_user_name']
            || !$params['readonly_user_password']
            || (!empty($existingParams['readonly_user_name']) && $existingParams['readonly_user_name'] === $params['readonly_user_name'])
        ) {
            return true;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $services->get('Omeka\Connection');
        $username = $connection->quote($params['readonly_user_name']);
        $password = $connection->quote($params['readonly_user_password']);
        $host = $connection->getHost();
        $database = $connection->getDatabase();

        // Check if the user exists.
        $sql = <<<SQL
SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = $username);
SQL;
        try {
            $result = $connection->fetchColumn($sql);
        } catch (\Exception $e) {
            $controller->messenger()->addError(
                'The Omeka database user has no rights to check or create a user. Add it manually yourself if needed.' // @translate
            );
            return true;
        }
        if ($result) {
            return true;
        }

        $sqls = <<<SQL
CREATE USER $username@'$host' IDENTIFIED BY $password;
GRANT SELECT ON `$database`.* TO $username@'$host';
FLUSH PRIVILEGES;
SQL;
        try {
            foreach (explode("\n", $sqls) as $sql) {
                $connection->exec($sql);
            }
            $controller->messenger()->addSuccess(sprintf(
                'The read-only user "%s" has been created.', // @translate
                $username
            ));
        } catch (\Exception $e) {
            $controller->messenger()->addError(sprintf(
                'An error occurred during the creation of the read-only user "%s".', // @translate
                $username
            ));
            return true;
        }

        return true;
    }

    protected function isWriteableFile($filepath)
    {
        return file_exists($filepath)
            ? is_writeable($filepath)
            : is_writeable(dirname($filepath));
    }
}
