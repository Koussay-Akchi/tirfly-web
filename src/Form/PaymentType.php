<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Data;
use App\Entity\Payment;
use App\Entity\Reservation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code')
            ->add('dateSubmit', null, [
                'widget' => 'single_text'
            ])
            ->add('message')
            ->add('refund')
            ->add('status')
            ->add('client', EntityType::class, [
                'class' => Client::class,
'choice_label' => 'id',
            ])
            ->add('data', EntityType::class, [
                'class' => Data::class,
'choice_label' => 'id',
            ])
            ->add('reservation', EntityType::class, [
                'class' => Reservation::class,
'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payment::class,
        ]);
    }
}
