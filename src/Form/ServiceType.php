<?php

namespace App\Form;

use App\Entity\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('icon', TextType::class, [
                'label' => 'Icône',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: heart-pulse, x-ray, baby, truck-medical',
                ],
                'help' => 'Nom de l\'icône Font Awesome (sans le préfixe "fa-"). Exemples: heart-pulse, x-ray, baby, truck-medical, lungs, brain, tooth, syringe'
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du service',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG)',
                    ])
                ],
                'help' => 'Image représentative du service (JPEG, PNG, max 2Mo)'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Service::class,
        ]);
    }
}
