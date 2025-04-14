<?php

// src/Form/PackType.php

namespace App\Form;

use App\Entity\Pack;
use App\Entity\Voyage;
use App\Entity\Sejour;
use App\Entity\Hebergement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('nom', TextType::class, [
            'label' => 'Nom du Pack :',
            'attr' => ['placeholder' => 'Entrez le nom du pack'],
        ])
    ->add('prix', NumberType::class, [
        'label' => 'Prix du Pack :',
    ])
    ->add('voyages', EntityType::class, [
        'class' => Voyage::class,
        'choice_label' => function(Voyage $voyage) {
            return $voyage->getNom() . ' - ' . $voyage->getPrix() . ' €';
        },
        'multiple' => true,
        'expanded' => false,
        'attr' => [
            'class' => 'form-select select2-multiple',
            'data-placeholder' => 'Sélectionnez un ou plusieurs voyages'
        ]
    ])
    ->add('sejours', EntityType::class, [
        'class' => Sejour::class,
        'choice_label' => 'hebergement.nom',
        'multiple' => true,
        'expanded' => false,
        'label' => 'Séjours inclus :',
    ])
    
    ->add('image', FileType::class, [
        'label' => 'Image du Pack :',
        'mapped' => false,
        'required' => false,
        'constraints' => [
            new File([
                'maxSize' => '2048k',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                    'image/jpg',
                ],
                'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, JPEG, PNG)',
            ])
        ],
    ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Pack::class,
        ]);
    }
}


