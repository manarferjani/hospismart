<?php


namespace App\Form;

use App\Entity\Disponibilite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
// N'oubliez pas cet import :
use Symfony\Component\Form\Extension\Core\Type\DateTimeType; 

class DisponibiliteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date_debut', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Début',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    // C'est ici qu'on bloque le calendrier HTML5
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'), 
                ],
            ])
            ->add('date_fin', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Fin',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Disponibilite::class,
        ]);
    }
}