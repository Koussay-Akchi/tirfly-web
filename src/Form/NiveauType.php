<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Niveau;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NiveauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('MaxNiveauXP')
            ->add('niveau')
            ->add('niveauXP')
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'email', // Adjust based on available Client properties
                'disabled' => true, // Prevent changing during edit
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Niveau::class,
        ]);
    }
}
