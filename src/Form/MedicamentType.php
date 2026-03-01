<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Medicament;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MedicamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du médicament',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ex : Paracétamol 500mg',
                    'autofocus' => true,
                ],
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité en stock',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => '0',
                ],
            ])
            ->add('seuilAlerte', IntegerType::class, [
                'label' => 'Seuil d\'alerte',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Quantité minimum avant alerte',
                ],
            ])
            ->add('prixUnitaire', NumberType::class, [
                'label' => 'Prix unitaire (€)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('datePeremption', DateType::class, [
                'label' => 'Date de péremption',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                ],
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'form-select form-select-lg',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Medicament::class,
            'csrf_protection' => true,
        ]);
    }
}
