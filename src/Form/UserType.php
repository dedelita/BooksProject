<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'user.username'
            ])
            ->add('email', EmailType::class, [
                'label' => 'user.email'
            ])
            ->add('changePassword', CheckboxType::class, [
                'label' => 'password.set',
                'mapped' => false,
                'required' => false
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => "password.current",
                'mapped' => false,
                'required' => false,
            ])
            ->add("plainPassword", RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'password.same',
                'first_options'  => ['label' => "password.new"],
                'second_options' => ['label' => "password.conf"],
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'minMessage' => 'password.length',
                        'max' => 4096,
                    ]),]
            ])
            /* ->add('password', PasswordType::class, ["mapped" => false, 'required' => false, 
            'constraints' => [
                new Length([
                    'min' => 8,
                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                    'max' => 4096,
                ]),]])
            ->add('confPassword', PasswordType::class, ["mapped" => false, 'required' => false, 'constraints' => [
                new Length([
                    'min' => 8,
                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                    'max' => 4096,
                ]),]]) */
                ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
