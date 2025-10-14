<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Category Name',
                'attr' => [
                    'class' => 'w-full border border-amber-200 rounded-xl px-4 py-3 text-yellow-900 focus:ring-2 focus:ring-rose-300 focus:outline-none placeholder:text-amber-300',
                    'placeholder' => 'e.g. Red Wine, White Wine, Sparkling Wine',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'w-full border border-amber-200 rounded-xl px-4 py-3 text-yellow-900 focus:ring-2 focus:ring-rose-300 focus:outline-none placeholder:text-amber-300',
                    'rows' => 4,
                    'placeholder' => 'Describe this category...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
