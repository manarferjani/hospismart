<?php

namespace App\Form;

use App\Entity\Medicament;
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
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ex : Paracétamol 500mg',
                    'autofocus' => true,
                ],
            ])
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité en stock',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => '0',
                ],
            ])
            ->add('seuilAlerte', IntegerType::class, [
                'label' => 'Seuil d\'alerte',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Quantité minimum avant alerte',
                ],
            ])
            ->add('prixUnitaire', NumberType::class, [
                'label' => 'Prix unitaire (€)',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => '0.00',
                ],
            ])
            ->add('datePeremption', DateType::class, [
                'label' => 'Date de péremption',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Medicament::class,
            'csrf_protection' => false,
        ]);
    }
}
