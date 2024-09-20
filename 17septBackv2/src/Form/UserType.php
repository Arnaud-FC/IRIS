<?php

namespace App\Form;

use App\Entity\LanguagesAvailable;
use App\Entity\User;
use App\Entity\Visite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('roles')
            ->add('password')
            ->add('lastName')
            ->add('firstName')
            ->add('picture')
            ->add('description')
            ->add('languagesAvailables', EntityType::class, [
                'class' => LanguagesAvailable::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('visites', EntityType::class, [
                'class' => Visite::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
