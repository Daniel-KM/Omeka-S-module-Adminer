<?php declare(strict_types=1);
namespace Adminer;

use Adminer\Form\ConfigForm;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;
use Omeka\Mvc\Controller\Plugin\Messenger;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
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

        $services = $this->getServiceLocator();
        $form = $services->get('FormElementManager')->get(ConfigForm::class);
        $form->init();
        $form->setData($dbConfig);

        return $renderer->formCollection($form);
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
        if (!$this->isWriteableFile($filepath)) {
            $controller->messenger()->addError('The file config/database-adminer.ini is not writeable, so credentials cannot be updated.'); // @translate
            return false;
        }

        $reader = new \Laminas\Config\Reader\Ini();
        $existingParams = $reader->fromFile($filepath);

        $params = array_intersect_key($params, array_flip([
            'default_user_name',
            'default_user_password',
            'main_user_name',
            'main_user_password',
        ]));

        $writer = new \Laminas\Config\Writer\Ini();
        $writer->toFile($filepath, $params);

        // Try to create the read-only user only if new.
        if ((!$params['default_user_name'] || !$params['default_user_password'])
            || (!empty($existingParams['default_user_name']) && $existingParams['default_user_name'] === $params['default_user_name'])
        ) {
            return true;
        }

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $services->get('Omeka\Connection');
        $username = $connection->quote($params['default_user_name']);
        $password = $connection->quote($params['default_user_password']);
        $host = $connection->getHost();
        $database = $connection->getDatabase();

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
