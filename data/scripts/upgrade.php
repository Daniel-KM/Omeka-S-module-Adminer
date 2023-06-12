<?php declare(strict_types=1);

namespace Adminer;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (version_compare($oldVersion, '3.4.3-4.8.1', '<')) {
    $filepath = OMEKA_PATH . '/config/database-adminer.ini';
    if (file_exists($filepath) && is_readable($filepath) && filesize($filepath)) {
        $reader = new \Laminas\Config\Reader\Ini();
        $dbConfig = $reader->fromFile($filepath);
        $settings->set('adminer_readonly_user', $dbConfig['readonly_user_name'] ?: null);
        $settings->set('adminer_readonly_password', $dbConfig['readonly_user_password'] ?: null);
    }
    @unlink($filepath);

    $message = new Message(
        'The file database-adminer.ini has been removed. Read-only user credentials are now stored in database. Full access user parameters have been removed.' // @translate
    );
    $messenger->addSuccess($message);
}
