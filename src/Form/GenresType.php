<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Genre;
use App\Repository\GenreRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GenresType extends AbstractType
{
    public function __construct(private GenreRepository $genreRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('genres', ChoiceType::class, [
                'label' => 'Genres',
                'choices' => $this->genreRepository->getGenres(),
                'choice_value' => 'id',
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => true,
                'required' => false,
            ])
        ;
    }
}