<?php

namespace App\Form;

use App\Entity\Consultation;
use App\Entity\User;
use App\Enum\ConsultationStatus;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsultationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_heure', null, [
                'label' => '📅 Date et Heure',
                'widget' => 'single_text',
            ])
            ->add('statut', EnumType::class, [
                'class' => ConsultationStatus::class,
                'label' => '📌 Statut',
            ])
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
                'label' => '👤 Patient'
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
                'label' => '👨‍⚕️ Médecin'
            ])
            ->add('motif', TextareaType::class, [
                'label' => '🩺 Motif de consultation',
                'attr' => ['rows' => 2]
            ])
            ->add('examen_clinique', TextareaType::class, [
                'label' => '🔎 Examen clinique',
                'attr' => ['rows' => 4]
            ])
            ->add('diagnostic', TextareaType::class, [
                'label' => '🧠 Diagnostic',
                'attr' => ['rows' => 2]
            ])
            ->add('traitement', TextareaType::class, [
                'label' => '💊 Traitement & Ordonnance',
                'attr' => ['rows' => 4]
            ])
            ->add('examens_complementaires', TextareaType::class, [
                'label' => '📄 Examens à faire',
                'required' => false,
                'attr' => ['rows' => 2]
            ])
            ->add('recommandations', TextareaType::class, [
                'label' => '📅 Suivi & Conseils',
                'required' => false,
                'attr' => ['rows' => 2]
            ])
            ->add('observations', TextareaType::class, [
                'label' => '📝 Observations (Détails)',
                'required' => false,
                'attr' => ['rows' => 2]
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