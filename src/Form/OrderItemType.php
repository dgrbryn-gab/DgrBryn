<?php

namespace App\Form;

use App\Entity\OrderItem;
use App\Entity\StoreProduct;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\CallbackTransformer;

class OrderItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', EntityType::class, [
                'label' => 'Product',
                'class' => StoreProduct::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->orderBy('p.name', 'ASC');
                },
                'choice_label' => function(StoreProduct $product) {
                    return sprintf('%s (₱%.2f)', $product->getName(), $product->getPrice());
                },
                'choice_attr' => function(StoreProduct $product) {
                    return ['data-price' => number_format($product->getPrice(), 2, '.', '')];
                },
                'placeholder' => '-- Select a product --',
                'required' => true,
                'attr' => [
                    'class' => 'form-control product-select',
                    'style' => 'width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'required' => true,
                'attr' => [
                    'class' => 'form-control quantity-field',
                    'style' => 'width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;',
                    'placeholder' => 'Qty',
                    'min' => 1,
                ],
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Unit Price',
                'currency' => 'PHP',
                'required' => true,
                'attr' => [
                    'class' => 'form-control unit-price-field',
                    'style' => 'width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;',
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ],
            ])
            ->add('discount', MoneyType::class, [
                'label' => 'Discount (optional)',
                'currency' => 'PHP',
                'required' => false,
                'attr' => [
                    'class' => 'form-control discount-field',
                    'style' => 'width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;',
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderItem::class,
        ]);
    }
}
