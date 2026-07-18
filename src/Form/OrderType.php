<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', null, [
                'label' => 'Votre prénom',
                'attr' => [
                    'class' => 'form form-control',
                    'placeholder' => 'Entrer votre prénom',
                ]
            ])
            ->add('lastName', null, [
                'label' => 'Votre nom',
                'attr' => [
                    'class' => 'form form-control',
                    'placeholder' => 'Entrer votre nom',
                ]
            ])
            ->add('email', null, [
                'label' => 'Votre adresse email',
                'attr' => [
                    'class' => 'form form-control',
                    'placeholder' => 'Entrer votre adresse email',
                ]
            ])
            ->add('phone', null, [
                'label' => 'Votre numéro de téléphone',
                'attr' => [
                    'class' => 'form form-control',
                    'placeholder' => 'Entrer votre numéro de téléphone',
                ]
            ])
            ->add('adress', null, [
                'label' => 'Votre adresse',
                'attr' => [
                    'class' => 'form form-control',
                    'placeholder' => 'Entrer votre adresse',
                ]
            ])
           // ->add('createdAt', null, ['widget' => 'single_text'])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'label' => 'Votre ville',
                'attr' => [
                    'class' => 'form form-control',
                ]
            ])
            ->add('payOnDelivery', null, [
                'label' => 'Payer à la livraison  ',
                'attr' => [
                    'class' => 'form-check-input',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
