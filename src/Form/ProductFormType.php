<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'nom du produit', 'attr' => ['class' => 'form-control']])
            ->add('description', TextareaType::class, ['label' => 'description du produit', 'attr' => ['class' => 'form-control']])
            ->add('price', IntegerType::class, ['label' => 'prix du produit', 'attr' => ['class' => 'form-control']])
            ->add('stock', IntegerType::class, ['label' => 'quantité disponible du produit', 'attr' => ['class' => 'form-control']])
            ->add('imageFile', FileType::class, ['label' => 'image du produit', 'attr' => ['class' => 'form-control'], 'multiple' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
