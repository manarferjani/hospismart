<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_heure')
            ->add('statut')
            ->add('motif')
            ->add('observations')
             ->add('patient', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                },
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.type = :type')
                        ->setParameter('type', 'PATIENT')
                        ->orderBy('u.nom', 'ASC');
                },
            ])
            ->add('medecin', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return 'Dr. ' . $user->getNom() . ' ' . $user->getPrenom();
                },
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.type = :type')
                        ->setParameter('type', 'MEDECIN')
                        ->orderBy('u.nom', 'ASC');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consultation::class,
        ]);
    }
}
