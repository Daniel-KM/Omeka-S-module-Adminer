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
        $unixSocket = $iniConfig['unix_socket'] ?? '';

        // Map PDO driverOptions to Adminer SSL config.
        $driverOptions = $iniConfig['driverOptions'] ?? [];
        $sslConfig = [];
        if ($driverOptions) {
            // PDO::MYSQL_ATTR_SSL_CA = 1009.
            if (isset($driverOptions[\PDO::MYSQL_ATTR_SSL_CA])) {
                $sslConfig['ca'] = $driverOptions[\PDO::MYSQL_ATTR_SSL_CA];
            }
            // PDO::MYSQL_ATTR_SSL_KEY = 1007.
            if (isset($driverOptions[\PDO::MYSQL_ATTR_SSL_KEY])) {
                $sslConfig['key'] = $driverOptions[\PDO::MYSQL_ATTR_SSL_KEY];
            }
            // PDO::MYSQL_ATTR_SSL_CERT = 1008.
            if (isset($driverOptions[\PDO::MYSQL_ATTR_SSL_CERT])) {
                $sslConfig['cert'] = $driverOptions[\PDO::MYSQL_ATTR_SSL_CERT];
            }
            // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = 1014.
            if (isset($driverOptions[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT])) {
                $sslConfig['verify'] = (bool) $driverOptions[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT];
            }
        }

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

        // Build server string: host:port or host:unix_socket.
        $server = $host;
        if ($unixSocket !== '') {
            $server .= ':' . $unixSocket;
        } elseif ($port !== '') {
            $server .= ':' . $port;
        }

        return new IndexController(
            [
                'server' => $server,
                'db' => $dbName,
                'full_user_name' => $dbUserName,
                'full_user_password' => $dbUserPassword,
                'ssl' => $sslConfig,
            ]
        );
    }
}
