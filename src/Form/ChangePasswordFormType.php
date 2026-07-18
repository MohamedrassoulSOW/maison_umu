<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'first_options' => [
                'label' => 'Nouveau mot de passe',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control',
                    'data-password-toggle' => '1',
                ],
            ],
            'second_options' => [
                'label' => 'Confirmer le mot de passe',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control',
                    'data-password-toggle' => '1',
                ],
            ],
            'invalid_message' => 'Les mots de passe ne correspondent pas.',
            'constraints' => [
                new NotBlank(message: 'Indiquez un mot de passe.'),
                new Length(
                    min: 6,
                    max: 4096,
                    minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
