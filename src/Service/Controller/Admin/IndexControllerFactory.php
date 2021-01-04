<?php declare(strict_types=1);
namespace Adminer\Service\Controller\Admin;

use Adminer\Controller\Admin\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $iniConfig = $services->get('Omeka\Connection')->getParams();
        return new IndexController(
            ['server' => $iniConfig['host'], 'db' => $iniConfig['dbname']]
        );
    }
}
