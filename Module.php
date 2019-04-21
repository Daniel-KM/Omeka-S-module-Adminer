<?php

namespace Adminer;

use Omeka\Module\AbstractModule;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;
use Zend\Permissions\Acl\Acl;
use Zend\ServiceManager\ServiceLocatorInterface;
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
        $db_config = $reader->fromFile(OMEKA_PATH . '/modules/Adminer/config/database.ini');

        $configForm = new Form\ConfigForm();
        $configForm->init();
        $configForm->setData($db_config);

        return $renderer->formCollection($configForm);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $writer = new \Zend\Config\Writer\Ini();

        $settings = $controller->params()->fromPost();

        $writer->toFile(OMEKA_PATH . "/modules/Adminer/config/database.ini", $settings);
        return true;
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        parent::install($serviceLocator);
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        parent::uninstall($serviceLocator);
    }
}