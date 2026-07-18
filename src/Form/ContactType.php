<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Indiquez votre nom.'),
                    new Length(max: 100),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'Indiquez votre email.'),
                    new Email(message: 'Email invalide.'),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'constraints' => [
                    new NotBlank(message: 'Indiquez un sujet.'),
                    new Length(max: 150),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['rows' => 6],
                'constraints' => [
                    new NotBlank(message: 'Écrivez votre message.'),
                    new Length(min: 10, minMessage: 'Votre message doit contenir au moins {{ limit }} caractères.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
