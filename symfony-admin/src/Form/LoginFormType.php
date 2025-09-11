<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Login form type for admin authentication
 */
final class LoginFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Email jest wymagany']),
                    new Assert\Email(['message' => 'Podaj prawidłowy adres email']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'admin@example.com',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Hasło',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Hasło jest wymagane']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź hasło',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Zaloguj się',
                'attr' => ['class' => 'btn btn-primary w-100'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
