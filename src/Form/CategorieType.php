<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Ex : Antibiotiques',
                    'autofocus' => true,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Description optionnelle de la catégorie...',
                    'rows' => 3,
                ],
            ])
            ->add('medicaments', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'class' => \App\Entity\Medicament::class,
                'choice_label' => 'nom',
                'label' => 'Médicaments associés',
                'multiple' => true,
                'expanded' => false, // Liste déroulante multiple (Ctrl+Click)
                'required' => false,
                'by_reference' => false, // Important pour appeler addMedicament() et setCategorie()
                'attr' => [
                    'class' => 'form-select',
                    'style' => 'height: 200px;', // Plus haut pour voir plusieurs items
                    'data-placeholder' => 'Sélectionnez les médicaments à ajouter...',
                ],
                'help' => 'Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs médicaments.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
            'csrf_protection' => true,
        ]);
    }
}
