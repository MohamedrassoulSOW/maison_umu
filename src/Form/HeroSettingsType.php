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
            ->add('mediaFile', FileType::class, [
                'label' => 'Média du hero (photo ou vidéo)',
                'mapped' => false,
                'required' => false,
                'help' => 'Photo : JPG, PNG, WEBP (max 8 Mo). Vidéo : MP4, WEBM (max 40 Mo). Laissez vide pour conserver le média actuel.',
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '40M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'video/mp4',
                            'video/webm',
                            'video/quicktime',
                            'video/ogg',
                        ],
                        'mimeTypesMessage' => 'Choisissez une photo (JPG, PNG, WEBP) ou une vidéo (MP4, WEBM).',
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
