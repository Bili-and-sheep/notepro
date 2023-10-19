<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\ClassLevel;
use App\Entity\Evaluation;
use App\Entity\Professor;
use App\Entity\Subject;
use App\Repository\ClassLevelRepository;
use App\Repository\SubjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $eval = $options['data'];
        $prof = $eval->getProfessor();

        $subjects = $prof->getSubjects();
        $classes = [];
        $classes = $prof->getClassLevels();

        $builder
            ->add('date', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('label', TextType::class, [
                'label' => 'Titre de l\'évaluation'
            ])
            ->add('bareme', IntegerType::class, [
                'label' => 'Barème (/20, /10, ...)',
                'attr' => [
                    'min' => 0,
                    'max' => 100,
                ],
            ])
            ->add('subject', EntityType::class, [
                'class' => Subject::class,
                'label' => 'Matière',
                'choice_label' => 'label',
                'expanded' => false,
                'multiple' => false,
                'query_builder' => function(SubjectRepository $er) use($prof){
                    return $er->findByProfessor($prof);
                },
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'label' => 'Categorie',
                'placeholder' => 'Sélectionner une catégorie',
                'choice_label' => 'label',
                'expanded' => false,
                'multiple' => false,
            ])

            ->add('classLevel', EntityType::class, [
                'class' => ClassLevel::class,
                'label' => 'Classe',
                'choice_label' => 'label',
                'expanded' => false,
                'multiple' => false,
                'query_builder' => function(ClassLevelRepository $er) use($prof){
                    return $er->findByProfessor($prof);
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
        ]);
    }
}
