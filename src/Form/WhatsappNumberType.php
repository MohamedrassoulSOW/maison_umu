<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class WhatsappNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', TextType::class, [
                'label' => 'Pays / libellé',
                'constraints' => [new NotBlank(), new Length(max: 80)],
            ])
            ->add('label', TextType::class, [
                'label' => 'Numéro affiché',
                'help' => 'Ex. +221 78 450 78 08',
                'constraints' => [new NotBlank(), new Length(max: 80)],
            ])
            ->add('href', UrlType::class, [
                'label' => 'Lien WhatsApp',
                'help' => 'Ex. https://wa.me/221784507808 (chiffres uniquement après wa.me/)',
                'default_protocol' => 'https',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
