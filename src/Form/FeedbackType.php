<?php

namespace App\Form;

use App\Entity\Feedback;
use App\Entity\Voyage;
use App\Entity\Client;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$options['hide_voyage_client']) {
            $builder
                ->add('client', EntityType::class, [
                    'class' => Client::class,
                    'choice_label' => 'nom',
                    'label' => 'Client',
                    'required' => true,
                ])
                ->add('voyage', EntityType::class, [
                    'class' => Voyage::class,
                    'choice_label' => function (Voyage $voyage) {
                        return $voyage->getDestination() ? $voyage->getDestination()->getVille() . ' (' . $voyage->getDestination()->getPays() . ')' : 'Destination inconnue';
                    },
                    'label' => 'Voyage',
                    'required' => true,
                ]);
        }

        $builder
            ->add('note', IntegerType::class, [
                'label' => 'Note (1 à 5)',
                'attr' => ['min' => 1, 'max' => 5],
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu du Feedback',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFeedback', HiddenType::class, [
                'data' => (new \DateTime())->format('Y-m-d H:i:s'),
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
            'hide_voyage_client' => false,
        ]);
    }
}