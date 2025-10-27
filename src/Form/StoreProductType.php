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
use Symfony\Component\Form\Extension\Core\Type\FileType; // <-- NEW

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
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white']
            ])
            // CHANGE: Use FileType + point to imageFile (not image)
            ->add('imageFile', FileType::class, [
                'label' => 'Product Image',
                'required' => false,
                'mapped' => false, // Not a DB field
                'attr' => [
                    'class' => 'w-full border border-gray-300 rounded-md px-3 py-2 text-gray-800 bg-white cursor-pointer',
                    'accept' => 'image/*'
                ],
                'help' => 'Max 5 MB – JPEG, PNG, WebP'
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