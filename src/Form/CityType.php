<?php

namespace App\Form;

use App\Entity\City;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'required' => false,
                'attr'=> ['class' => 'form form-control', 'placeholder' => 'Nom de la ville'],
                'label' => 'Nom de la ville',
            ])
            ->add('shippingConst', null, [
                'required' => false,
                'attr'=> ['class' => 'form form-control', 'placeholder' => '0.00 CFA'],
                'label' => 'Prix de livraison',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
        ]);
    }
}
