<?php declare(strict_types=1);

namespace AdminerTest;

use Adminer\Module;
use CommonTest\AbstractHttpControllerTestCase;

/**
 * Tests for the Adminer Module class.
 */
class ModuleTest extends AbstractHttpControllerTestCase
{
    use AdminerTestTrait;

    /**
     * @var Module
     */
    protected $module;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
        $this->module = new Module();
    }

    /**
     * Test that getConfig returns an array.
     */
    public function testGetConfigReturnsArray(): void
    {
        $config = $this->module->getConfig();

        $this->assertIsArray($config);
    }

    /**
     * Test that getConfig contains required keys.
     */
    public function testGetConfigContainsRequiredKeys(): void
    {
        $config = $this->module->getConfig();

        $this->assertArrayHasKey('controllers', $config);
        $this->assertArrayHasKey('router', $config);
        $this->assertArrayHasKey('navigation', $config);
    }

    /**
     * Test that controller is registered in config.
     */
    public function testControllerIsRegistered(): void
    {
        $config = $this->module->getConfig();

        $this->assertArrayHasKey('controllers', $config);
        $this->assertArrayHasKey('factories', $config['controllers']);
        $this->assertArrayHasKey(
            \Adminer\Controller\Admin\IndexController::class,
            $config['controllers']['factories']
        );
    }

    /**
     * Test that routes are defined.
     */
    public function testRoutesAreDefined(): void
    {
        $config = $this->module->getConfig();

        $this->assertArrayHasKey('router', $config);
        $this->assertArrayHasKey('routes', $config['router']);
        $this->assertArrayHasKey('admin', $config['router']['routes']);
        $this->assertArrayHasKey('child_routes', $config['router']['routes']['admin']);
        $this->assertArrayHasKey('adminer', $config['router']['routes']['admin']['child_routes']);
    }

    /**
     * Test that navigation is configured.
     */
    public function testNavigationIsConfigured(): void
    {
        $config = $this->module->getConfig();

        $this->assertArrayHasKey('navigation', $config);
        $this->assertArrayHasKey('AdminModule', $config['navigation']);
    }

    /**
     * Test that form element manager has ConfigForm.
     */
    public function testConfigFormIsRegistered(): void
    {
        $config = $this->module->getConfig();

        $this->assertArrayHasKey('form_elements', $config);
        $this->assertArrayHasKey('invokables', $config['form_elements']);
        $this->assertArrayHasKey(
            \Adminer\Form\ConfigForm::class,
            $config['form_elements']['invokables']
        );
    }

    /**
     * Test uninstall removes settings.
     */
    public function testUninstallRemovesSettings(): void
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        // Set test values.
        $settings->set('adminer_readonly_user', 'test_user');
        $settings->set('adminer_readonly_password', 'test_pass');
        $settings->set('adminer_full_access', true);

        // Verify they are set.
        $this->assertEquals('test_user', $settings->get('adminer_readonly_user'));

        // Call uninstall.
        $this->module->uninstall($services);

        // Verify they are removed.
        $this->assertNull($settings->get('adminer_readonly_user'));
        $this->assertNull($settings->get('adminer_readonly_password'));
        $this->assertNull($settings->get('adminer_full_access'));
    }
}
