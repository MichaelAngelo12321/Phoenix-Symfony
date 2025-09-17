<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Dto\FilterDto;
use App\Form\UserFilterType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class UserFilterTypeTest extends TypeTestCase
{

    public function testSubmitValidData(): void
    {
        $formData = [
            'firstName' => 'Jan',
            'lastName' => 'Kowalski',
            'gender' => '1', // Using numeric value as per GenderEnum::fromString
            'birthdateFrom' => '1990-01-01',
            'birthdateTo' => '2000-12-31',
            'sortBy' => 'first_name',
            'sortOrder' => 'desc',
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
    }

    public function testEmptyFormIsValid(): void
    {
        $form = $this->factory->create(UserFilterType::class);
        $form->submit([]);

        $this->assertTrue($form->isValid());
    }

    public function testFirstNameTooLong(): void
    {
        $formData = [
            'firstName' => str_repeat('a', 51),
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('firstName')->getErrors()->count() > 0);
    }

    public function testFirstNameInvalidCharacters(): void
    {
        $formData = [
            'firstName' => 'Jan123',
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('firstName')->getErrors()->count() > 0);
    }

    public function testLastNameTooLong(): void
    {
        $formData = [
            'lastName' => str_repeat('a', 51),
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('lastName')->getErrors()->count() > 0);
    }

    public function testLastNameInvalidCharacters(): void
    {
        $formData = [
            'lastName' => 'Kowalski123',
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('lastName')->getErrors()->count() > 0);
    }

    public function testValidLastNameWithHyphen(): void
    {
        $formData = [
            'lastName' => 'Kowalski-Nowak',
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
    }

    public function testValidLastNameWithSpace(): void
    {
        $formData = [
            'lastName' => 'Van Der Berg',
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
    }

    public function testBirthdateFromInFuture(): void
    {
        $formData = [
            'birthdateFrom' => (new \DateTime('+1 day'))->format('Y-m-d'),
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('birthdateFrom')->getErrors()->count() > 0);
    }

    public function testBirthdateToInFuture(): void
    {
        $formData = [
            'birthdateTo' => (new \DateTime('+1 day'))->format('Y-m-d'),
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('birthdateTo')->getErrors()->count() > 0);
    }

    public function testValidGenderChoices(): void
    {
        $validGenders = ['1', '2', null]; // Using numeric values as per GenderEnum

        foreach ($validGenders as $gender) {
            $formData = [
                'gender' => $gender,
            ];

            $form = $this->factory->create(UserFilterType::class);
            $form->submit($formData);

            $this->assertTrue($form->isValid(), "Gender {$gender} should be valid");
        }
    }

    public function testValidSortByChoices(): void
    {
        $validSortBy = ['id', 'first_name', 'last_name', 'birthdate', 'gender'];

        foreach ($validSortBy as $sortBy) {
            $formData = [
                'sortBy' => $sortBy,
            ];

            $form = $this->factory->create(UserFilterType::class);
            $form->submit($formData);

            $this->assertTrue($form->isValid(), "SortBy {$sortBy} should be valid");
        }
    }

    public function testValidSortOrderChoices(): void
    {
        $validSortOrders = ['asc', 'desc'];

        foreach ($validSortOrders as $sortOrder) {
            $formData = [
                'sortOrder' => $sortOrder,
            ];

            $form = $this->factory->create(UserFilterType::class);
            $form->submit($formData);

            $this->assertTrue($form->isValid(), "SortOrder {$sortOrder} should be valid");
        }
    }

    public function testDefaultSortValues(): void
    {
        $form = $this->factory->create(UserFilterType::class);

        $this->assertEquals('id', $form->get('sortBy')->getData());
        $this->assertEquals('asc', $form->get('sortOrder')->getData());
    }

    public function testCsrfProtectionDisabled(): void
    {
        $form = $this->factory->create(UserFilterType::class);
        $config = $form->getConfig();

        $this->assertFalse($config->getOption('csrf_protection'));
    }

    public function testFormDataClass(): void
    {
        $form = $this->factory->create(UserFilterType::class);
        $config = $form->getConfig();

        $this->assertEquals(FilterDto::class, $config->getOption('data_class'));
    }

    public function testFormMethod(): void
    {
        $form = $this->factory->create(UserFilterType::class);
        $config = $form->getConfig();

        $this->assertEquals('GET', $config->getOption('method'));
    }

    public function testFormHasCorrectFields(): void
    {
        $form = $this->factory->create(UserFilterType::class);

        $this->assertTrue($form->has('firstName'));
        $this->assertTrue($form->has('lastName'));
        $this->assertTrue($form->has('gender'));
        $this->assertTrue($form->has('birthdateFrom'));
        $this->assertTrue($form->has('birthdateTo'));
        $this->assertTrue($form->has('sortBy'));
        $this->assertTrue($form->has('sortOrder'));
    }

    public function testPolishCharactersInNames(): void
    {
        $formData = [
            'firstName' => 'Łukasz',
            'lastName' => 'Żółć-Ąćęłńóśźż',
        ];

        $form = $this->factory->create(UserFilterType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
    }

    public function testEmptyBlockPrefix(): void
    {
        $formType = new UserFilterType();
        $this->assertEquals('', $formType->getBlockPrefix());
    }

    public function testAllFieldsOptional(): void
    {
        $form = $this->factory->create(UserFilterType::class);

        $this->assertFalse($form->get('firstName')->isRequired());
        $this->assertFalse($form->get('lastName')->isRequired());
        $this->assertFalse($form->get('gender')->isRequired());
        $this->assertFalse($form->get('birthdateFrom')->isRequired());
        $this->assertFalse($form->get('birthdateTo')->isRequired());
        $this->assertFalse($form->get('sortBy')->isRequired());
        $this->assertFalse($form->get('sortOrder')->isRequired());
    }
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }
}
