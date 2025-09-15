<?php

declare(strict_types=1);

namespace App\Form;

use App\Enum\ValidatorMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'label' => 'Imię',
                'constraints' => [
                    new Assert\NotBlank(['message' => ValidatorMessage::FIRST_NAME_REQUIRED->value]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => ValidatorMessage::FIRST_NAME_TOO_SHORT->value,
                        'maxMessage' => ValidatorMessage::FIRST_NAME_TOO_LONG->value,
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]+$/',
                        'message' => 'Imię może zawierać tylko litery',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź imię',
                ],
            ])
            ->add('last_name', TextType::class, [
                'label' => 'Nazwisko',
                'constraints' => [
                    new Assert\NotBlank(['message' => ValidatorMessage::LAST_NAME_REQUIRED->value]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => ValidatorMessage::LAST_NAME_TOO_SHORT->value,
                        'maxMessage' => ValidatorMessage::LAST_NAME_TOO_LONG->value,
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\s-]+$/',
                        'message' => 'Nazwisko może zawierać tylko litery, spacje i myślniki',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź nazwisko',
                ],
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Data urodzenia',
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Data urodzenia jest wymagana']),
                    new Assert\LessThan([
                        'value' => 'today',
                        'message' => ValidatorMessage::BIRTHDATE_FUTURE->value,
                    ]),
                    new Assert\GreaterThan([
                        'value' => '1900-01-01',
                        'message' => ValidatorMessage::BIRTHDATE_TOO_OLD->value,
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'max' => date('Y-m-d'),
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Płeć',
                'choices' => [
                    'Kobieta' => 'female',
                    'Mężczyzna' => 'male',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Płeć jest wymagana']),
                    new Assert\Choice([
                        'choices' => ['female', 'male'],
                        'message' => 'Wybierz prawidłową płeć',
                    ]),

                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_edit' => false,
        ]);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
