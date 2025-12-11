<?php

namespace App\Form;

use App\Entity\StoreProduct;
use App\Entity\WineInventory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WineInventoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $staffProducts = $options['staff_products'];

        $productOptions = [
            'class' => StoreProduct::class,
            'choice_label' => 'name',
        ];

        // If staff_products is provided, filter the choices to only show staff's own products
        if ($staffProducts !== null) {
            $productOptions['choices'] = $staffProducts;
            $productOptions['placeholder'] = 'Select a product...';
        }

        $builder
            ->add('quantity')
            ->add('acquiredDate')
            ->add('lastUpdated')
            ->add('product', EntityType::class, $productOptions)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WineInventory::class,
            'staff_products' => null,
        ]);
    }
}
