<?php

namespace App\Form;

use App\Entity\Reponse;
use App\Entity\Reclamation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_reclamation']) {
            $builder->add('reclamation', EntityType::class, [
                'class' => Reclamation::class,
                'choice_label' => function (Reclamation $reclamation) {
                    return '#' . $reclamation->getId() . ' - ' . $reclamation->getTitre() . ' (' . $reclamation->getNomPatient() . ')';
                },
                'label' => 'Réclamation concernée',
                'placeholder' => 'Sélectionnez une réclamation',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ]);
        }

        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu de la réponse',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 6,
                    'placeholder' => 'Rédigez votre réponse ici...',
                ],
            ])
            ->add('adminNom', TextType::class, [
                'label' => 'Nom de l\'administrateur',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom',
                ],
            ])
            ->add('adminEmail', TextType::class, [
                'label' => 'Email de l\'administrateur',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'admin@hopital.com',
                    'type' => 'email',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reponse::class,
            'include_reclamation' => true,
        ]);
    }
}