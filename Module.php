<?php
namespace Adminer;

use Adminer\Form\ConfigForm;
use Omeka\Module\AbstractModule;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $filepath = __DIR__ . '/config/database-adminer.ini';
        if (!$this->isWriteableFile($filepath)) {
            $messenger = new Messenger();
            $messenger->addWarning('The file Adminer/config/database-adminer.ini is not writeable, so credentials cannot be updated.'); // @translate
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
        $filepath = __DIR__ . '/config/database-adminer.ini';
        if (!$this->isWriteableFile($filepath)) {
            $controller->messenger()->addErrors('The file Adminer/config/database-adminer.ini is not writeable, so credentials cannot be updated.'); // @translate
            return false;
        }

        $params = array_intersect_key($params, array_flip([
            'default_user_name',
            'default_user_password',
            'main_user_name',
            'main_user_password',
        ]));

        $writer = new \Laminas\Config\Writer\Ini();
        $writer->toFile($filepath, $params);
        return true;
    }

    protected function isWriteableFile($filepath)
    {
        return file_exists($filepath)
            ? is_writeable($filepath)
            : is_writeable(dirname($filepath));
    }
}
