<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\FilterDto;
use App\Enum\GenderEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class UserFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'required' => false,
                'label' => 'Imię',
                'attr' => [
                    'placeholder' => 'Wpisz imię...',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Imię nie może być dłuższe niż {{ limit }} znaków',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]+$/',
                        'message' => 'Imię może zawierać tylko litery',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'required' => false,
                'label' => 'Nazwisko',
                'attr' => [
                    'placeholder' => 'Wpisz nazwisko...',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Nazwisko nie może być dłuższe niż {{ limit }} znaków',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\s-]+$/',
                        'message' => 'Nazwisko może zawierać tylko litery, spacje i myślniki',
                    ]),
                ],
            ])
            ->add('gender', ChoiceType::class, [
                'required' => false,
                'label' => 'Płeć',
                'choices' => [
                    'Wszystkie' => null,
                    'Mężczyzna' => GenderEnum::MALE->value,
                    'Kobieta' => GenderEnum::FEMALE->value,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('birthdateFrom', DateType::class, [
                'required' => false,
                'label' => 'Data urodzenia od',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'Data nie może być z przyszłości',
                    ]),
                ],
            ])
            ->add('birthdateTo', DateType::class, [
                'required' => false,
                'label' => 'Data urodzenia do',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'Data nie może być z przyszłości',
                    ]),
                ],
            ])
            ->add('sortBy', ChoiceType::class, [
                'required' => false,
                'label' => 'Sortuj według',
                'choices' => [
                    'ID' => 'id',
                    'Imię' => 'first_name',
                    'Nazwisko' => 'last_name',
                    'Data urodzenia' => 'birthdate',
                    'Płeć' => 'gender',
                ],
                'data' => 'id',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('sortOrder', ChoiceType::class, [
                'required' => false,
                'label' => 'Kierunek sortowania',
                'choices' => [
                    'Rosnąco' => 'asc',
                    'Malejąco' => 'desc',
                ],
                'data' => 'asc',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FilterDto::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
