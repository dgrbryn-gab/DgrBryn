<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StoreProduct;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customerName', TextType::class, [
                'label' => 'Customer Name',
                'required' => false,
            ])
            ->add('customerEmail', EmailType::class, [
                'label' => 'Customer Email',
                'required' => false,
            ])
            ->add('customerPhone', TelType::class, [
                'label' => 'Customer Phone',
                'required' => false,
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Shipping Address',
                'required' => false,
            ])
            ->add('billingAddress', TextareaType::class, [
                'label' => 'Billing Address',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Order Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Processing' => 'processing',
                    'Shipped' => 'shipped',
                    'Delivered' => 'delivered',
                    'Cancelled' => 'cancelled',
                ],
                'required' => true,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Credit Card' => 'credit_card',
                    'Debit Card' => 'debit_card',
                    'PayPal' => 'paypal',
                    'Bank Transfer' => 'bank_transfer',
                    'Cash' => 'cash',
                ],
                'required' => false,
            ])
            ->add('paymentStatus', ChoiceType::class, [
                'label' => 'Payment Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Completed' => 'completed',
                    'Failed' => 'failed',
                    'Refunded' => 'refunded',
                ],
                'required' => false,
            ])
            ->add('shippingCost', MoneyType::class, [
                'label' => 'Shipping Cost',
                'currency' => 'PHP',
                'required' => false,
            ])
            ->add('taxAmount', MoneyType::class, [
                'label' => 'Tax Amount',
                'currency' => 'PHP',
                'required' => false,
            ])
            ->add('discountAmount', MoneyType::class, [
                'label' => 'Discount Amount',
                'currency' => 'PHP',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Order Notes',
                'required' => false,
            ])
            ->add('orderItems', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'label' => 'Order Items',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'validation_groups' => ['Default'],
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'order';
    }
}
