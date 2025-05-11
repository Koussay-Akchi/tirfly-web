<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom du produit'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Le nom du produit est obligatoire'])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 5,
                    'placeholder' => 'Description du produit...'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire'])
                ]
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Catégorie du produit'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La catégorie est obligatoire'])
                ]
            ])
            ->add('prixUnitaire', NumberType::class, [
                'label' => 'Prix unitaire (€)',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => '0.01'
                ],
                'html5' => true,
                'scale' => 2,
                'constraints' => [
                    new NotBlank(['message' => 'Le prix unitaire est obligatoire']),
                    new PositiveOrZero(['message' => 'Le prix doit être supérieur ou égal à 0'])
                ]
            ])
            ->add('quantiteStock', NumberType::class, [
                'label' => 'Quantité en stock',
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0
                ],
                'html5' => true,
                'constraints' => [
                    new NotBlank(['message' => 'La quantité en stock est obligatoire']),
                    new PositiveOrZero(['message' => 'La quantité doit être supérieure ou égale à 0'])
                ]
            ])
            ->add('ecoFriendly', CheckboxType::class, [
                'label' => 'Produit éco-responsable',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label']
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/webp'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}