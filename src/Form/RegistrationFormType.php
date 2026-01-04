<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Registration form type for user registration.
 *
 * Validates:
 * - Email format and required
 * - Password minimum length (8 chars) and strength
 * - Password confirmation matching
 * - Terms acceptance checkbox
 *
 * Based on auth-spec.md section 1.3.1 and 2.3.2
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'twój@email.pl',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Email jest wymagany',
                    ]),
                    new Email([
                        'message' => 'Podaj prawidłowy adres email',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Hasło',
                'mapped' => false, // Not persisted to entity
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Minimum 8 znaków',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Hasło jest wymagane',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Hasło musi zawierać co najmniej {{ limit }} znaków',
                        'max' => 4096, // Security: max length to prevent DoS
                    ]),
                    new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        'message' => 'Hasło jest zbyt słabe. Użyj kombinacji liter, cyfr i znaków specjalnych',
                    ]),
                ],
            ])
            ->add('passwordConfirm', PasswordType::class, [
                'label' => 'Potwierdź hasło',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Wpisz hasło ponownie',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Potwierdź swoje hasło',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => false, // Label is rendered in template
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Musisz zaakceptować regulamin',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No data_class - we handle data manually in controller
            // This allows us to use Domain model without direct form mapping
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'registration',
        ]);
    }
}
