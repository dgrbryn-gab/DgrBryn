<?php

namespace App\Form;

use App\Entity\StoreProduct;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class StoreProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white']
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price',
                'scale' => 2,
                'attr' => ['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white']
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Initial Stock Quantity',
                'mapped' => false, // Not mapped to StoreProduct
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white']
            ])
            ->add('image', TextType::class, [
                'label' => 'Image Path',
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white']
            ])
            ->add('isAvailable', CheckboxType::class, [
                'label' => 'Is Available',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 text-rose-600 border-gray-300 rounded focus:ring-rose-400']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'required' => false,
                'placeholder' => 'Select a category',
                'attr' => ['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StoreProduct::class,
        ]);
    }
}