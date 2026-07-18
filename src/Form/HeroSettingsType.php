<?php

namespace App\Form;

use App\Entity\HeroSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class HeroSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brandLabel', TextType::class, [
                'label' => 'Marque (petit texte)',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('headline', TextType::class, [
                'label' => 'Titre principal',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('text', TextareaType::class, [
                'label' => 'Texte d’accompagnement',
                'attr' => ['rows' => 4],
                'constraints' => [new NotBlank()],
            ])
            ->add('primaryCtaLabel', TextType::class, [
                'label' => 'Bouton principal — texte',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('primaryCtaUrl', TextType::class, [
                'label' => 'Bouton principal — lien',
                'help' => 'Ex. #collection ou /about',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('secondaryCtaLabel', TextType::class, [
                'label' => 'Bouton secondaire — texte',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('secondaryCtaUrl', TextType::class, [
                'label' => 'Bouton secondaire — lien',
                'help' => 'Ex. /cart ou /contact',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du hero',
                'mapped' => false,
                'required' => false,
                'help' => 'JPG, PNG ou WEBP — max 4 Mo. Laissez vide pour conserver l’image actuelle.',
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Choisissez une image valide (JPG, PNG ou WEBP).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HeroSettings::class,
        ]);
    }
}
