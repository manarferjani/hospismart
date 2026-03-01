<?php

namespace App\Form;

use App\Entity\Medicament;
use App\Entity\MouvementStock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MouvementStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Type de mouvement',
                'choices' => [
                    'Entrée de stock' => 'ENTREE',
                    'Sortie de stock' => 'SORTIE',
                ],
                'attr' => [
                    'class' => 'form-select form-select-lg',
                ],
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ex : 50',
                ],
            ])
            ->add('dateMouvement', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ex : Livraison fournisseur, sortie pour consultation...',
                    'rows' => 3,
                ],
            ])
            ->add('medicament', EntityType::class, [
                'class' => Medicament::class,
                'label' => 'Médicament',
                'choice_label' => 'nom',
                'placeholder' => 'Choisir un médicament',
                'attr' => [
                    'class' => 'form-select form-select-lg',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MouvementStock::class,
            'csrf_protection' => false,
        ]);
    }
}
