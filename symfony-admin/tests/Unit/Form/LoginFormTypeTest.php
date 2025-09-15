<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\LoginFormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

final class LoginFormTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $form = $this->factory->create(LoginFormType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($formData['email'], $form->get('email')->getData());
        $this->assertEquals($formData['password'], $form->get('password')->getData());
    }

    public function testEmailRequired(): void
    {
        $formData = [
            'email' => '',
            'password' => 'password123',
        ];

        $form = $this->factory->create(LoginFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('email')->getErrors()->count() > 0);
    }

    public function testPasswordRequired(): void
    {
        $formData = [
            'email' => 'admin@example.com',
            'password' => '',
        ];

        $form = $this->factory->create(LoginFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('password')->getErrors()->count() > 0);
    }

    public function testInvalidEmailFormat(): void
    {
        $formData = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $form = $this->factory->create(LoginFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('email')->getErrors()->count() > 0);
    }

    public function testEmailWithoutAtSymbol(): void
    {
        $formData = [
            'email' => 'adminexample.com',
            'password' => 'password123',
        ];

        $form = $this->factory->create(LoginFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('email')->getErrors()->count() > 0);
    }

    public function testEmailWithoutDomain(): void
    {
        $formData = [
            'email' => 'admin@',
            'password' => 'password123',
        ];

        $form = $this->factory->create(LoginFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('email')->getErrors()->count() > 0);
    }

    public function testCsrfProtectionEnabled(): void
    {
        $form = $this->factory->create(LoginFormType::class);
        $config = $form->getConfig();

        $this->assertTrue($config->getOption('csrf_protection'));
    }

    public function testFormHasCorrectFields(): void
    {
        $form = $this->factory->create(LoginFormType::class);

        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('password'));
        $this->assertTrue($form->has('submit'));
    }

    public function testEmailFieldType(): void
    {
        $form = $this->factory->create(LoginFormType::class);
        $emailField = $form->get('email');

        $this->assertEquals('Symfony\Component\Form\Extension\Core\Type\EmailType', $emailField->getConfig()->getType()->getInnerType()::class);
    }

    public function testPasswordFieldType(): void
    {
        $form = $this->factory->create(LoginFormType::class);
        $passwordField = $form->get('password');

        $this->assertEquals('Symfony\Component\Form\Extension\Core\Type\PasswordType', $passwordField->getConfig()->getType()->getInnerType()::class);
    }

    public function testSubmitButtonType(): void
    {
        $form = $this->factory->create(LoginFormType::class);
        $submitField = $form->get('submit');

        $this->assertEquals('Symfony\Component\Form\Extension\Core\Type\SubmitType', $submitField->getConfig()->getType()->getInnerType()::class);
    }

    public function testValidEmailFormats(): void
    {
        $validEmails = [
            'user@example.com',
            'test.email@domain.co.uk',
            'user+tag@example.org',
            'user123@test-domain.com',
        ];

        foreach ($validEmails as $email) {
            $formData = [
                'email' => $email,
                'password' => 'password123',
            ];

            $form = $this->factory->create(LoginFormType::class);
            $form->submit($formData);

            $this->assertTrue($form->isValid(), "Email {$email} should be valid");
        }
    }

    public function testInvalidEmailFormats(): void
    {
        $invalidEmails = [
            'plainaddress',
            '@missingdomain.com',
            'missing@.com',
            'missing@domain',
            'spaces @domain.com',
            'double@@domain.com',
        ];

        foreach ($invalidEmails as $email) {
            $formData = [
                'email' => $email,
                'password' => 'password123',
            ];

            $form = $this->factory->create(LoginFormType::class);
            $form->submit($formData);

            $this->assertFalse($form->isValid(), "Email {$email} should be invalid");
            $this->assertTrue($form->get('email')->getErrors()->count() > 0);
        }
    }
}