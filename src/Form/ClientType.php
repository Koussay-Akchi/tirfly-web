<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr' => ['class' => 'form-control', 'placeholder' => 'example@domain.com'],
                'required' => true,
            ])
            ->add('motDePasse', PasswordType::class, [
                'label' => 'Password',
                'attr' => ['class' => 'form-control'],
                'required' => true,
                'mapped' => false,
            ])
            ->add('nom', TextType::class, [
                'label' => 'Last Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your last name'],
                'required' => true,
            ])
            ->add('prenom', TextType::class, [
                'label' => 'First Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your first name'],
                'required' => true,
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Address',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your address'],
                'required' => false,
            ])
            ->add('age', IntegerType::class, [
                'label' => 'Age',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your age'],
                'required' => true,
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\GreaterThan([
                        'value' => 17,
                        'message' => 'You must be at least {{ compared_value }} years old.',
                    ]),
                ],
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Gender',
                'choices' => [
                    'Male' => 'Homme',
                    'Female' => 'Femme',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone Number',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter your phone number'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'csrf_protection' => false, // Disable CSRF // Unique token ID for this form
        ]);
    }
}