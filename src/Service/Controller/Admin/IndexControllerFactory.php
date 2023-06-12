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

        $dbName = $iniConfig['dbname'];
        $host = $iniConfig['host'];
        $port = $iniConfig['port'] ?? '';

        /** @var \Omeka\Settings\Settings $settings */
        $settings = $services->get('Omeka\Settings');
        $fullAccess = (bool) $settings->get('adminer_full_access');
        if ($fullAccess) {
            $dbUserName = $iniConfig['user'];
            $dbUserPassword = $iniConfig['password'];
        } else {
            $dbUserName = '';
            $dbUserPassword = '';
        }

        return new IndexController(
            [
                'server' => $host . (empty($port) ? '' : (':' . $port)),
                'db' => $dbName,
                'full_user_name' => $dbUserName,
                'full_user_password' => $dbUserPassword,
            ]
        );
    }
}
