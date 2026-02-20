<?php

namespace App\Form;

use App\Entity\ParticipantEvenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InscriptionParticipantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userConnected = $options['user_connected'] ?? false;

        if (!$userConnected) {
            $builder
                ->add('nom', TextType::class, [
                    'label' => 'Nom',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Votre nom'],
                ])
                ->add('prenom', TextType::class, [
                    'label' => 'Prénom',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Votre prénom'],
                ])
                ->add('email', EmailType::class, [
                    'label' => 'Email',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'votre@email.com'],
                ]);
        }

        $builder
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Optionnel'],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle souhaité',
                'choices' => [
                    'Participant' => 'participant',
                    'Observateur' => 'observateur',
                ],
                'data' => 'participant',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('confirme_presence', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Je confirme ma présence',
                'data' => true,
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipantEvenement::class,
            'user_connected' => false,
        ]);
        $resolver->setAllowedTypes('user_connected', 'bool');
    }
}
