<?php

namespace App\Form;

use App\Entity\ParticipantEvenement;
use App\Entity\Evenement;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantEvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('evenement', EntityType::class, [
                'label' => 'Événement',
                'class' => Evenement::class,
                'choice_label' => 'titre',
                'attr' => ['class' => 'form-control']
            ])
            ->add('participant', EntityType::class, [
                'label' => 'Participant',
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                },
                'attr' => ['class' => 'form-control']
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Organisateur' => 'organisateur',
                    'Intervenant' => 'intervenant',
                    'Participant' => 'participant',
                    'Observateur' => 'observateur',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('confirme_presence', CheckboxType::class, [
                'label' => 'Confirme présence',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('date_confirmation', DateTimeType::class, [
                'label' => 'Date de confirmation',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipantEvenement::class,
        ]);
    }
}
