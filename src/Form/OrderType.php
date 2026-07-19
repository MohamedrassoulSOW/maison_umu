<?php

namespace App\Form;

use App\Entity\City;
use App\Entity\Order;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', null, [
                'label' => 'Votre prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrer votre prénom',
                ],
            ])
            ->add('lastName', null, [
                'label' => 'Votre nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrer votre nom',
                ],
            ])
            ->add('email', null, [
                'label' => 'Votre adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrer votre adresse email',
                ],
            ])
            ->add('phone', null, [
                'label' => 'Votre numéro de téléphone',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrer votre numéro de téléphone',
                ],
            ])
            ->add('adress', null, [
                'label' => 'Votre adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrer votre adresse',
                ],
            ])
            ->add('city', EntityType::class, [
                'class' => City::class,
                'choice_label' => 'name',
                'label' => 'Votre ville',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Wave Sénégal' => 'wave',
                    'Orange Money' => 'orange_money',
                    'Livraison' => 'cod',
                    'Carte' => 'stripe',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [new NotBlank()],
                'attr' => [
                    'class' => 'umu-pay-methods',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
