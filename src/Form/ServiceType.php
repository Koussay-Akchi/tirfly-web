<?php
// src/Form/ServiceType.php
namespace App\Form;

use App\Entity\Service;
use App\Entity\Hebergement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints as Assert;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du service',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: WiFi, Piscine, Petit-déjeuner'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le nom du service est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-Z0-9\s\-éèêëàâäôöûüçÉÈÊËÀÂÄÔÖÛÜÇ]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, chiffres, espaces et tirets'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,  // Changed from false to true
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description est obligatoire'
                    ]),
                    new Assert\Length([
                        'min' => 10,  // Added minimum length
                        'max' => 500,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères'
                    ])
                ]
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (€)',
                'required' => true,  // Changed from false to true
                'attr' => [
                    'class' => 'form-control',
                    'step' => '0.01'
                ],
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le prix est obligatoire'
                    ]),
                    new Assert\Type([
                        'type' => 'float',
                        'message' => 'Le prix doit être un nombre décimal'
                    ]),
                    new Assert\PositiveOrZero([
                        'message' => 'Le prix ne peut pas être négatif'
                    ]),
                    new Assert\LessThan([
                        'value' => 10000,
                        'message' => 'Le prix ne peut pas dépasser {{ value }}€'
                    ])
                ]
            ])
            ->add('hebergement', EntityType::class, [
                'label' => 'Hébergement associé',
                'class' => Hebergement::class,
                'choice_label' => 'nom',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Veuillez sélectionner un hébergement'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}