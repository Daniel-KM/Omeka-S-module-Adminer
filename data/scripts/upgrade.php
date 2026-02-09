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
 * @var \Omeka\View\Helper\Url $url
 * @var \Laminas\Log\Logger $logger
 * @var \Omeka\Settings\Settings $settings
 * @var \Laminas\I18n\View\Helper\Translate $translate
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Laminas\Mvc\I18n\Translator $translator
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Settings\SiteSettings $siteSettings
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$logger = $services->get('Omeka\Logger');
$settings = $services->get('Omeka\Settings');
$translate = $plugins->get('translate');
$translator = $services->get('MvcTranslator');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$siteSettings = $services->get('Omeka\Settings\Site');
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

if (version_compare($oldVersion, '3.4.6-5.1.0', '<')) {
    $message = new Message(
        'The libraries were updated. Check them if you customized them.' // @translate
    );
    $messenger->addSuccess($message);
}

if (version_compare($oldVersion, '3.4.8-5.4.1', '<')) {
    $settings->delete('adminer_readonly_user');
    $settings->delete('adminer_readonly_password');
}

// Remove the read-only user credentials if they don't work.
$readonlyUser = $settings->get('adminer_readonly_user');
$readonlyPassword = $settings->get('adminer_readonly_password');
if ($readonlyUser || $readonlyPassword) {
    $removeReadonly = !$readonlyUser || !$readonlyPassword;
    if (!$removeReadonly) {
        $params = $connection->getParams();
        try {
            $pdo = new \PDO(
                'mysql:host=' . ($params['host'] ?? 'localhost')
                    . ';port=' . ($params['port'] ?? '3306')
                    . ';dbname=' . ($params['dbname'] ?? ''),
                $readonlyUser,
                $readonlyPassword
            );
            $pdo = null;
        } catch (\Exception $e) {
            $removeReadonly = true;
        }
    }
    if ($removeReadonly) {
        $settings->delete('adminer_readonly_user');
        $settings->delete('adminer_readonly_password');
        $messenger->addWarning(new Message(
            'The read-only user credentials have been removed because they were not working.' // @translate
        ));
    }
}
