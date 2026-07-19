<?php

namespace App\Form;

use App\Entity\FooterSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FooterSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('showCategories', CheckboxType::class, [
                'label' => 'Afficher les catégories',
                'required' => false,
            ])
            ->add('showBrand', CheckboxType::class, [
                'label' => 'Afficher la marque (logo, slogan, email)',
                'required' => false,
            ])
            ->add('showNavigation', CheckboxType::class, [
                'label' => 'Afficher la navigation (Boutique, À propos, Contact…)',
                'required' => false,
            ])
            ->add('showAccount', CheckboxType::class, [
                'label' => 'Afficher le compte (Favoris, Panier, Connexion…)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FooterSettings::class,
        ]);
    }
}
