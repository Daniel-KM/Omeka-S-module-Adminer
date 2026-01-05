<?php declare(strict_types=1);

namespace AdminerTest\Form;

use Adminer\Form\ConfigForm;
use CommonTest\AbstractHttpControllerTestCase;
use AdminerTest\AdminerTestTrait;

/**
 * Tests for the Adminer ConfigForm.
 */
class ConfigFormTest extends AbstractHttpControllerTestCase
{
    use AdminerTestTrait;

    /**
     * @var ConfigForm
     */
    protected $form;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
        $this->form = new ConfigForm();
        $this->form->init();
    }

    /**
     * Test form initialization creates all elements.
     */
    public function testFormHasAllElements(): void
    {
        $this->assertTrue($this->form->has('adminer_readonly_user'));
        $this->assertTrue($this->form->has('adminer_readonly_password'));
        $this->assertTrue($this->form->has('adminer_full_access'));
    }

    /**
     * Test readonly user field is text type.
     */
    public function testReadonlyUserIsTextField(): void
    {
        $element = $this->form->get('adminer_readonly_user');
        $this->assertInstanceOf(\Laminas\Form\Element\Text::class, $element);
    }

    /**
     * Test readonly password field is password type.
     */
    public function testReadonlyPasswordIsPasswordField(): void
    {
        $element = $this->form->get('adminer_readonly_password');
        $this->assertInstanceOf(\Laminas\Form\Element\Password::class, $element);
    }

    /**
     * Test full access field is checkbox type.
     */
    public function testFullAccessIsCheckboxField(): void
    {
        $element = $this->form->get('adminer_full_access');
        $this->assertInstanceOf(\Laminas\Form\Element\Checkbox::class, $element);
    }

    /**
     * Test form accepts valid data.
     */
    public function testFormAcceptsValidData(): void
    {
        $data = [
            'adminer_readonly_user' => 'readonly_user',
            'adminer_readonly_password' => 'secret123',
            'adminer_full_access' => '1',
        ];

        $this->form->setData($data);

        $this->assertTrue($this->form->isValid());
        $formData = $this->form->getData();
        $this->assertEquals('readonly_user', $formData['adminer_readonly_user']);
        $this->assertEquals('secret123', $formData['adminer_readonly_password']);
        $this->assertEquals('1', $formData['adminer_full_access']);
    }

    /**
     * Test form accepts empty data.
     */
    public function testFormAcceptsEmptyData(): void
    {
        $data = [
            'adminer_readonly_user' => '',
            'adminer_readonly_password' => '',
            'adminer_full_access' => '0',
        ];

        $this->form->setData($data);

        $this->assertTrue($this->form->isValid());
    }

    /**
     * Test form elements have labels.
     */
    public function testFormElementsHaveLabels(): void
    {
        $this->assertNotEmpty($this->form->get('adminer_readonly_user')->getLabel());
        $this->assertNotEmpty($this->form->get('adminer_readonly_password')->getLabel());
        $this->assertNotEmpty($this->form->get('adminer_full_access')->getLabel());
    }
}
