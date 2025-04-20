<?php

namespace App\Form;

use App\Entity\Reclamation;
use App\Entity\Reponse;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['limited_fields'] ?? false) {
            $builder
                ->add('sujet')
                ->add('contenu');
        } else {$builder
            ->add('contenu')
            ->add('dateReponse', null, [
                'widget' => 'single_text'
            ])
            ->add('sujet')
            ->add('reclamation', EntityType::class, [
                'class' => Reclamation::class,
'choice_label' => 'id',
            ])
            ->add('auteur', EntityType::class, [
                'class' => User::class,
'choice_label' => 'id',
            ])
        ;
    }
}
public function configureOptions(OptionsResolver $resolver): void
{
    $resolver->setDefaults([
        'data_class' => Reponse::class,
        'limited_fields' => false,
    ]);

    $resolver->setAllowedTypes('limited_fields', 'bool');
}
}