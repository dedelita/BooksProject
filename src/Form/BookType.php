<?php

namespace App\Form;

use App\Entity\Book;
use App\Form\CommentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => false, 'attr' => [
                "placeholder" => "book.title",
            ]])
            ->add('author', TextType::class, ['label' => false, 
            'required' => false,
            'attr' => [
                'placeholder' => 'book.author',
            ]])
            ->add('language', ChoiceType::class, ['label' => "book.lang", 'choices' =>[
                'Français' => "fr",
                'English' => "en",
                'Español' => "es",
                'Italiano' => "it"
            ]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
