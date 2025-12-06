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
                'placeholder' => '-- Select a product --',
                'required' => true,
                'attr' => [
                    'class' => 'form-control product-select',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Quantity',
                    'min' => 1,
                    'value' => 1,
                ],
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Unit Price',
                'currency' => 'PHP',
                'required' => true,
                'attr' => [
                    'class' => 'form-control unit-price-field',
                    'placeholder' => '0.00',
                    'step' => '0.01',
                ],
            ])
            ->add('discount', MoneyType::class, [
                'label' => 'Discount (optional)',
                'currency' => 'PHP',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
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
