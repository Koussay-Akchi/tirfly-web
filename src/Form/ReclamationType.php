<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Reclamation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la réclamation :',
                'attr' => ['placeholder' => 'Entrez un titre'],
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description :',
                'attr' => ['placeholder' => 'Décrivez votre problème'],
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer une description.']),
                ],
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État de la réclamation :',
                'choices' => [
                    'Problème avec la réservation' => 'Problème avec la réservation',
                    'Qualité de service' => 'Qualité de service',
                    'Retard' => 'Retard',
                    'Annulation' => 'Annulation',
                ],
                'expanded' => false,
                'multiple' => false,
            ])
   
            
            ->add('videoPath', FileType::class, [
                'label' => 'Vidéo (facultatif) :',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => ['video/mp4', 'video/mpeg', 'video/quicktime'],
                        'mimeTypesMessage' => 'Veuillez télécharger une vidéo valide (MP4, MPEG, MOV).',
                    ])
                ],
            ])
            ->add('isRed', ChoiceType::class, [
                'label' => 'Réclamation lue ?',
                'choices' => [
                    'Oui' => '1',
                    'Non' => '0',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' =>  function (Client $client) {
                    return $client->getNom(); // Directly fetching from the parent class
                },  // Affiche le nom du client au lieu de l'ID
                'label' => 'Client :',
                'placeholder' => 'Sélectionnez un client',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
