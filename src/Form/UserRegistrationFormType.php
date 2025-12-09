<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Determine default role: ROLE_STAFF for new users, first role for existing users
        $defaultRole = 'ROLE_STAFF';
        if ($options['data'] instanceof User && $options['data']->getId()) {
            $roles = $options['data']->getRoles();
            $defaultRole = !empty($roles) ? $roles[0] : 'ROLE_STAFF';
        }
        
        $builder
            // Username field
            ->add('username', TextType::class, [
                'label' => 'Username',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a username']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Username must be at least {{ limit }} characters',
                        'max' => 180,
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'username',
                ],
            ])

            // Email field
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an email address']),
                    new Email(['message' => 'The email "{{ value }}" is not a valid email.']),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'user@example.com',
                ],
            ])

            // Role Selection (single role)
            ->add('roles', ChoiceType::class, [
                'label' => 'User Role',
                'choices' => [
                    'Administrator' => 'ROLE_ADMIN',
                    'Staff Member' => 'ROLE_STAFF',
                ],
                'expanded' => false,
                'multiple' => false,
                'mapped' => false,            
                'data' => $defaultRole,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])

            // Password field
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                        'placeholder' => 'Enter password',
                    ],
                    'label' => 'Password',
                ],
                'second_options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control',
                        'placeholder' => 'Confirm password',
                    ],
                    'label' => 'Confirm Password',
                ],
                'invalid_message' => 'The password fields must match.',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,

            // REQUIRED FOR ADMIN FORMS
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'user_registration_form',
        ]);
    }
}
