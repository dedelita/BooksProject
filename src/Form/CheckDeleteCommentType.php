<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckDeleteCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('deleteCom', CheckboxType::class, [
                "label" =>'book.com.delete',
                "mapped" => false,
                "required" => false,
                "attr" => [
                    "onClick" => "checkModalCustomCbx(id)",
                ],
                'label_attr' => [
                    'class' => 'checkbox-custom',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
