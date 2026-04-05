<?php

namespace App\Form;

use App\Entity\Diagnostic;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DiagnosticType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu du diagnostic',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Entrez les détails du diagnostic',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le contenu ne peut pas être vide'),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 5000,
                        'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le contenu ne peut pas dépasser {{ limit }} caractères',
                    ]),
                ],
            ])
            ->add('probabiliteIa', NumberType::class, [
                'label' => 'Probabilité IA (%)',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entre 0 et 100',
                    'min' => 0,
                    'max' => 100,
                    'step' => 0.01,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La probabilité ne peut pas être vide'),
                    new Assert\Range([
                        'min' => 0,
                        'max' => 100,
                        'notInRangeMessage' => 'La probabilité doit être entre {{ min }}% et {{ max }}%',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Diagnostic::class,
        ]);
    }
}
