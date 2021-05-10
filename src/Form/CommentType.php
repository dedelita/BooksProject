<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('stars', HiddenType::class, ["label" => false, "attr" => ["class" => "rating-input", "min" => 0, "max" => 5]])
            ->add('content', TextareaType::class, ["label" => false, "attr" => ["placeholder" => "book.think", "rows" => 5]])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'empty_data' => new Comment(),
        ]);
    }
}
