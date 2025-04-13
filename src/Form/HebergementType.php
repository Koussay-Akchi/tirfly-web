<?php
namespace App\Form;

use App\Entity\Hebergement;
use App\Entity\Destination;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, ChoiceType, IntegerType, 
    DateType, FileType, TextareaType
};
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class HebergementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $type = $options['type'] ?? null;
    
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Hôtel' => 'hotel',
                    'Logement' => 'logement',
                    'Foyer' => 'foyer',
                ],
                'mapped' => false,
                'data' => $type,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner un type d\'hébergement'
                    ]),
                ],
                'attr' => [
                    'class' => 'd-none',
                    'data-controller' => 'hebergement-type'
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'hébergement',
                'attr' => ['placeholder' => 'Entrez le nom'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                ],
            ])
            ->add('classement', IntegerType::class, [
                'label' => 'Classement (1-5 étoiles)',
                'attr' => ['min' => 1, 'max' => 5],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le classement est obligatoire']),
                    new Assert\Positive(['message' => 'Le classement doit être un nombre positif']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'Le classement doit être entre {{ min }} et {{ max }} étoiles'
                    ]),
                ],
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact (téléphone/email)',
                'required' => false,
                'attr' => ['placeholder' => 'Optionnel'],
                'constraints' => [
                    new Assert\Length(['max' => 50]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez votre hébergement...'
                ],
                'constraints' => [
                    new Assert\Length(['max' => 500]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'image',
                'mapped' => false,
                'required' => false,
                'help' => 'Formats acceptés: JPG, PNG, WEBP (max 2Mo)',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPG, PNG, WEBP)',
                    ]),
                ],
            ])
            
            ->add('destination', EntityType::class, [
                'class' => Destination::class,
                'choice_label' => 'pays',
                'label' => 'Destination',
                'placeholder' => 'Sélectionnez une destination',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner une destination'
                    ]),
                ],
            ]);
    
        // Champs spécifiques selon le type
        $this->addSpecificFields($builder, $type);
    }

    private function addSpecificFields(FormBuilderInterface $builder, ?string $type): void
    {
        if (in_array($type, ['hotel', 'logement'])) {
            $builder->add('prix', IntegerType::class, [
                'label' => $type === 'hotel' ? 'Prix par nuit (€)' : 'Prix par mois (€)',
                'mapped' => false,
                'attr' => ['min' => 0],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire']),
                    new Assert\Positive([
                        'message' => 'Le prix doit être un nombre positif'
                    ]),
                ],
            ]);
        }
    
        if ($type === 'logement') {
            $builder->add('jourDispo', DateType::class, [
                'label' => 'Date de disponibilité',
                'widget' => 'single_text',
                'mapped' => false,
                'attr' => ['min' => date('Y-m-d')],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La date de disponibilité est obligatoire'
                    ]),
                    new Assert\GreaterThan([
                        'value' => 'today',
                        'message' => 'La date doit être ultérieure à aujourd\'hui'
                    ]),
                ],
            ]);
        }
    
        if ($type === 'foyer') {
            $builder
                ->add('frais', IntegerType::class, [
                    'label' => 'Frais d\'inscription (€)',
                    'mapped' => false,
                    'attr' => ['min' => 0],
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Les frais sont obligatoires'
                        ]),
                        new Assert\Positive([
                            'message' => 'Les frais doivent être positifs'
                        ]),
                    ],
                ])
                ->add('typeFoyer', ChoiceType::class, [
                    'label' => 'Type de foyer',
                    'mapped' => false,
                    'choices' => [
                        'Public' => 'public',
                        'Privé' => 'prive',
                    ],
                    'placeholder' => 'Sélectionnez un type',
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Veuillez sélectionner un type de foyer'
                        ]),
                    ],
                ])
                ->add('documents', FileType::class, [
                    'label' => 'Documents justificatifs',
                    'mapped' => false,
                    'required' => false,
                    'help' => 'Format accepté: PDF (max 5Mo)',
                    'constraints' => [
                        new Assert\File([
                            'maxSize' => '5M',
                            'mimeTypes' => ['application/pdf'],
                            'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide',
                        ]),
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Hebergement::class,
            'type' => null,
            'attr' => [
                'novalidate' => 'novalidate',
                'data-controller' => 'hebergement-form'
            ],
        ]);
    }
}