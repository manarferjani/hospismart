<?php

namespace App\Form;

use App\Entity\ParametreVital;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ParametreVitalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tension', TextType::class, [
                'label' => 'Tension (mmHg)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 120/80',
                    'pattern' => '^[0-9]{2,3}/[0-9]{2,3}$',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La tension ne peut pas être vide'),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{2,3}\/[0-9]{2,3}$/',
                        'message' => 'Le format doit être XXX/XXX (ex: 120/80)',
                    ]),
                ],
            ])
            ->add('temperature', NumberType::class, [
                'label' => 'Température (°C)',
                'required' => true,
                'scale' => 1,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entre 35 et 45',
                    'min' => 35,
                    'max' => 45,
                    'step' => 0.1,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La température ne peut pas être vide'),
                    new Assert\Range([
                        'min' => 35,
                        'max' => 45,
                        'notInRangeMessage' => 'La température doit être entre {{ min }}°C et {{ max }}°C',
                    ]),
                ],
            ])
            ->add('frequenceCardiaque', IntegerType::class, [
                'label' => 'Fréquence cardiaque (bpm)',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entre 40 et 200',
                    'min' => 40,
                    'max' => 200,
                ],
                'constraints' => [
                    new Assert\NotBlank(message: 'La fréquence cardiaque ne peut pas être vide'),
                    new Assert\Range([
                        'min' => 40,
                        'max' => 200,
                        'notInRangeMessage' => 'La fréquence cardiaque doit être entre {{ min }} et {{ max }} bpm',
                    ]),
                ],
            ])
            ->add('datePrise', DateTimeType::class, [
                'label' => 'Date et heure de prise',
                'required' => true,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'La date ne peut pas être vide'),
                    new Assert\LessThanOrEqual([
                        'value' => 'now',
                        'message' => 'La date ne peut pas être dans le futur',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParametreVital::class,
        ]);
    }
}
