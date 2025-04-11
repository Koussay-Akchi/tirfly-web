<?php

namespace App\Form;

use App\Entity\Chat;
use App\Entity\Client;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => fn(Client $client) => $client->getPrenom() . ' ' . $client->getNom(),
                'label' => 'Client',
                'placeholder' => 'Sélectionnez un client',
            ])
            ->add('support', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) => $user->getPrenom() . ' ' . $user->getNom(),
                'label' => 'Support',
                'placeholder' => 'Sélectionnez un support',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chat::class,
        ]);
    }
}
