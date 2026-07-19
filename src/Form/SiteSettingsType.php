<?php

namespace App\Form;

use App\Entity\SiteSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SiteSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('brandName', TextType::class, [
                'label' => 'Nom de la marque',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('brandTagline', TextType::class, [
                'label' => 'Slogan',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo (optionnel)',
                'mapped' => false,
                'required' => false,
                'help' => 'JPG, PNG ou WEBP — max 4 Mo. Laissez vide pour conserver le logo actuel.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Choisissez une image JPG, PNG ou WEBP.',
                    ]),
                ],
            ])
            ->add('contactEmail', EmailType::class, [
                'label' => 'Email de contact',
                'constraints' => [new NotBlank(), new Email(), new Length(max: 180)],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('hours', TextType::class, [
                'label' => 'Horaires',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('responseSla', TextType::class, [
                'label' => 'Délai de réponse',
                'constraints' => [new NotBlank(), new Length(max: 180)],
            ])
            ->add('contactLead', TextareaType::class, [
                'label' => 'Intro page Contact',
                'attr' => ['rows' => 3],
                'constraints' => [new NotBlank()],
            ])
            ->add('contactInfoText', TextareaType::class, [
                'label' => 'Texte bloc infos Contact',
                'attr' => ['rows' => 2],
                'constraints' => [new NotBlank()],
            ])
            ->add('mapEmbedUrl', TextType::class, [
                'label' => 'URL iframe carte (OpenStreetMap)',
                'help' => 'Collez l’URL src de l’iframe OpenStreetMap (export/embed).',
                'constraints' => [new NotBlank()],
            ])
            ->add('mapLinkUrl', TextType::class, [
                'label' => 'Lien « Ouvrir la carte »',
                'constraints' => [new NotBlank()],
            ])
            ->add('footerBlurb', TextType::class, [
                'label' => 'Complément slogan (footer)',
                'help' => 'Affiché après le slogan dans le pied de page.',
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('whatsappNumbers', CollectionType::class, [
                'label' => 'Numéros WhatsApp',
                'entry_type' => WhatsappNumberType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
            ])
            ->add('aboutLead', TextareaType::class, [
                'label' => 'Intro page À propos',
                'attr' => ['rows' => 3],
                'constraints' => [new NotBlank()],
            ])
            ->add('aboutBlock1Title', TextType::class, [
                'label' => 'Bloc 1 — titre',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('aboutBlock1Text', TextareaType::class, [
                'label' => 'Bloc 1 — texte',
                'attr' => ['rows' => 3],
                'constraints' => [new NotBlank()],
            ])
            ->add('aboutBlock2Title', TextType::class, [
                'label' => 'Bloc 2 — titre',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('aboutBlock2Text', TextareaType::class, [
                'label' => 'Bloc 2 — texte',
                'attr' => ['rows' => 3],
                'constraints' => [new NotBlank()],
            ])
            ->add('aboutBlock3Title', TextType::class, [
                'label' => 'Bloc 3 — titre',
                'constraints' => [new NotBlank(), new Length(max: 120)],
            ])
            ->add('aboutBlock3Text', TextareaType::class, [
                'label' => 'Bloc 3 — texte',
                'attr' => ['rows' => 3],
                'constraints' => [new NotBlank()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteSettings::class,
        ]);
    }
}
