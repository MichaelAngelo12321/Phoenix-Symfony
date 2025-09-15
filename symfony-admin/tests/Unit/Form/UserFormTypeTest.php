<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Form\UserFormType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

final class UserFormTypeTest extends TypeTestCase
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
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        
        if (!$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                echo "Form error: " . $error->getMessage() . "\n";
            }
        }
        
        $this->assertTrue($form->isValid());
        
        $data = $form->getData();
        $this->assertEquals('Jan', $data['first_name']);
        $this->assertEquals('Kowalski', $data['last_name']);
        $this->assertEquals('male', $data['gender']);
        $this->assertInstanceOf(\DateTime::class, $data['birthdate']);
    }

    public function testFirstNameRequired(): void
    {
        $formData = [
            'first_name' => '',
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('first_name')->getErrors()->count() > 0);
    }

    public function testLastNameRequired(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => '',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('last_name')->getErrors()->count() > 0);
    }

    public function testBirthdateRequired(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birthdate' => null,
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('birthdate')->getErrors()->count() > 0);
    }

    public function testGenderRequired(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('gender')->getErrors()->count() > 0);
    }

    public function testFirstNameTooShort(): void
    {
        $formData = [
            'first_name' => 'J',
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('first_name')->getErrors()->count() > 0);
    }

    public function testFirstNameTooLong(): void
    {
        $formData = [
            'first_name' => str_repeat('a', 51),
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('first_name')->getErrors()->count() > 0);
    }

    public function testFirstNameInvalidCharacters(): void
    {
        $formData = [
            'first_name' => 'Jan123',
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('first_name')->getErrors()->count() > 0);
    }

    public function testLastNameTooShort(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => 'K',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('last_name')->getErrors()->count() > 0);
    }

    public function testLastNameTooLong(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => str_repeat('a', 51),
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('last_name')->getErrors()->count() > 0);
    }

    public function testBirthdateInFuture(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('+1 day'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('birthdate')->getErrors()->count() > 0);
    }

    public function testBirthdateTooOld(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('1899-12-31'),
            'gender' => '1',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('birthdate')->getErrors()->count() > 0);
    }

    public function testInvalidGender(): void
    {
        $formData = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'birthdate' => new \DateTime('1990-01-01'),
            'gender' => 'invalid',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('gender')->getErrors()->count() > 0);
    }

    public function testCsrfProtectionEnabled(): void
    {
        $form = $this->factory->create(UserFormType::class);
        $config = $form->getConfig();

        $this->assertTrue($config->getOption('csrf_protection'));
    }

    public function testFormHasCorrectFields(): void
    {
        $form = $this->factory->create(UserFormType::class);

        $this->assertTrue($form->has('first_name'));
        $this->assertTrue($form->has('last_name'));
        $this->assertTrue($form->has('birthdate'));
        $this->assertTrue($form->has('gender'));
    }

    public function testPolishCharactersInNames(): void
    {
        $formData = [
            'first_name' => 'Łukasz',
            'last_name' => 'Żółć-Ąćęłńóśźż',
            'birthdate' => '1990-01-01',
            'gender' => 'male',
        ];

        $form = $this->factory->create(UserFormType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        
        $data = $form->getData();
        $this->assertEquals('Łukasz', $data['first_name']);
        $this->assertEquals('Żółć-Ąćęłńóśźż', $data['last_name']);
        $this->assertEquals('male', $data['gender']);
        $this->assertInstanceOf(\DateTime::class, $data['birthdate']);
    }
}