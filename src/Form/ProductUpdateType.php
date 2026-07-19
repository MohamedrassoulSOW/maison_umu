<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\SubCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;

class ProductUpdateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('Price')
            ->add('images', FileType::class, [
                'label' => 'Ajouter des images',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                    'multiple' => 'multiple',
                ],
                'help' => 'Ajoutez une ou plusieurs photos. Les images actuelles restent sauf suppression.',
                'constraints' => [
                    new Count(max: 12, maxMessage: 'Maximum {{ limit }} images à la fois.'),
                    new All([
                        new File([
                            'maxSize' => '2M',
                            'mimeTypes' => [
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                            ],
                            'mimeTypesMessage' => 'Chaque fichier doit être une image valide (JPG, PNG, WEBP).',
                        ]),
                    ]),
                ],
            ])
            ->add('subcategories', EntityType::class, [
                'class' => SubCategory::class,
                'choice_label' => 'name',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
