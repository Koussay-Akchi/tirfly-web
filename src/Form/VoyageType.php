<?php

namespace App\Form;

use App\Entity\Destination;
use App\Entity\Pack;
use App\Entity\Voyage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VoyageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateArrive', null, [
                'widget' => 'single_text',
            ])
            ->add('dateDepart', null, [
                'widget' => 'single_text',
            ])
            ->add('description')
            //->add('image')
            ->add('nom')
            ->add('prix')
            ->add('formule')
            ->add('destination', EntityType::class, [
                'class' => Destination::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Voyage::class,
        ]);
    }
}
