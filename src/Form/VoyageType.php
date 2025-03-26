<?php

namespace App\Form;

use App\Entity\Voyage;
use App\Entity\Destination;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom :',
                'attr' => ['placeholder' => 'Entrez le nom du voyage'],
            ])
            ->add('destination', EntityType::class, [
                'class' => Destination::class,
                'choice_label' => 'ville', 
                'label' => 'Destination :',
            ])
            ->add('dateDepart', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de départ :',
            ])
            ->add('dateArrive', DateType::class, [
                'widget' => 'single_text',
                'label' => "Date d'arrivée :",
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix :',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description :',
            ])
            ->add('formule', ChoiceType::class, [
                'choices' => [
                    'REPAS_SEUL'=>'repas',
                    'BOISSONS_SEULES'=>'boissons',
                    'AUCUN'=>'aucun',
                    'LES_DEUX'=>'les_deux',
                ],
                'label' => 'Formule :',
            ])
            ->add('image', FileType::class, [
                'label' => 'Image :',
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Voyage::class,
        ]);
    }
}
