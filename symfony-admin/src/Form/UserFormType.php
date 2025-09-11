<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User form type for creating and editing users
 */
final class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'label' => 'Imię',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Imię jest wymagane']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Imię musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Imię nie może być dłuższe niż {{ limit }} znaków',
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
                    new Assert\NotBlank(['message' => 'Nazwisko jest wymagane']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Nazwisko musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Nazwisko nie może być dłuższe niż {{ limit }} znaków',
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
                        'message' => 'Data urodzenia nie może być z przyszłości',
                    ]),
                    new Assert\GreaterThan([
                        'value' => '1900-01-01',
                        'message' => 'Data urodzenia nie może być wcześniejsza niż 1900 rok',
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
            ->add('submit', SubmitType::class, [
                'label' => 'Zapisz użytkownika',
                'attr' => [
                    'class' => 'btn btn-success',
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