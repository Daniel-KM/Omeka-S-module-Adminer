<?php declare(strict_types=1);

namespace AdminerTest;

use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * Shared test helpers for Adminer module tests.
 */
trait AdminerTestTrait
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var bool Whether admin is logged in.
     */
    protected bool $isLoggedIn = false;

    /**
     * Get the service locator.
     */
    protected function getServiceLocator(): ServiceLocatorInterface
    {
        if (isset($this->application) && $this->application !== null) {
            return $this->application->getServiceManager();
        }
        return $this->getApplication()->getServiceManager();
    }

    /**
     * Reset the cached service locator.
     */
    protected function resetServiceLocator(): void
    {
        $this->services = null;
    }

    /**
     * Login as admin user.
     */
    protected function loginAdmin(): void
    {
        $this->isLoggedIn = true;
        $this->ensureLoggedIn();
    }

    /**
     * Ensure admin is logged in on the current application instance.
     */
    protected function ensureLoggedIn(): void
    {
        $services = $this->getServiceLocator();
        $auth = $services->get('Omeka\AuthenticationService');

        if ($auth->hasIdentity()) {
            return;
        }

        $adapter = $auth->getAdapter();
        $adapter->setIdentity('admin@example.com');
        $adapter->setCredential('root');
        $auth->authenticate();
    }

    /**
     * Logout current user.
     */
    protected function logout(): void
    {
        $this->isLoggedIn = false;
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
    }

    /**
     * Get the Adminer settings.
     */
    protected function getAdminerSettings(): array
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        return [
            'adminer_readonly_user' => $settings->get('adminer_readonly_user', ''),
            'adminer_readonly_password' => $settings->get('adminer_readonly_password', ''),
            'adminer_full_access' => $settings->get('adminer_full_access', false),
        ];
    }

    /**
     * Set Adminer settings for testing.
     */
    protected function setAdminerSettings(array $data): void
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        if (array_key_exists('adminer_readonly_user', $data)) {
            $settings->set('adminer_readonly_user', $data['adminer_readonly_user']);
        }
        if (array_key_exists('adminer_readonly_password', $data)) {
            $settings->set('adminer_readonly_password', $data['adminer_readonly_password']);
        }
        if (array_key_exists('adminer_full_access', $data)) {
            $settings->set('adminer_full_access', $data['adminer_full_access']);
        }
    }

    /**
     * Check if Adminer dependencies are installed.
     */
    protected function hasDependencies(): bool
    {
        $filename = dirname(__DIR__, 2) . '/asset/vendor/adminer/adminer-mysql.phtml';
        return file_exists($filename);
    }
}
