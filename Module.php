<?php
namespace Adminer;

use Omeka\Module\AbstractModule;
use Zend\Mvc\Controller\AbstractController;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $reader = new \Zend\Config\Reader\Ini();
        $db_config = $reader->fromFile(__DIR__ . '/config/database.ini');

        $configForm = new Form\ConfigForm();
        $configForm->init();
        $configForm->setData($db_config);

        return $renderer->formCollection($configForm);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $writer = new \Zend\Config\Writer\Ini();

        $settings = $controller->params()->fromPost();

        $writer->toFile(__DIR__ . '/config/database.ini', $settings);
        return true;
    }
}
