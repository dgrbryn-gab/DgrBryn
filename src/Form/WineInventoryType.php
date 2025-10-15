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
        $builder
            ->add('quantity')
            ->add('acquiredDate')
            ->add('lastUpdated')
            ->add('product', EntityType::class, [
                'class' => StoreProduct::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WineInventory::class,
        ]);
    }
}
