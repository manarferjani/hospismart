<?php

namespace App\Form;

use App\Entity\Equipement;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EquipementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('reference')
            ->add('etat', ChoiceType::class, [
                'choices'  => [
                    'Opérationnel' => 'Opérationnel',
                    'Maintenance'  => 'Maintenance',
                    'En Panne'     => 'En Panne',
                ],
                'attr' => ['class' => 'form-select']
            ])
            // ->add('relation') <--- CETTE LIGNE A ÉTÉ SUPPRIMÉE
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'nom', // Affiche le NOM du service dans la liste déroulante
                'placeholder' => 'Choisir un service...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipement::class,
        ]);
    }
}