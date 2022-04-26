<?php

namespace App\Form;

use App\Entity\ProductItem;
use App\Entity\PurchaseOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseOrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recipientName', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The recipient name can not be null',
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'The recipient name cannot be longer than 50 characters',
                    ])
                ]
            ])
            ->add('recipientEmail', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The recipient email can not be null',
                    ]),
                    new Email([
                        'message' => 'The recipient email is not a valid email.',
                    ])
                ]
            ])
            ->add('recipientPhone', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The recipient phone can not be null',
                    ]),
                    new Length([
                        'max' => 11,
                        'maxMessage' => 'The recipient phone cannot be longer than 11 characters',
                        'min' => 10,
                        'minMessage' => 'The recipient phone cannot be short than 10 characters',
                    ])
                ]
            ])
            ->add('addressDelivery', TextareaType::class)
            ->add('shippingCost', NumberType::class, [
                'constraints' => [
                    new Length([
                        'min' => 0,
                        'minMessage' => 'The shipping cost cannot be short than 0 characters',
                    ])
                ]
            ])
            ->add('totalPrice', NumberType::class, [
                'constraints' => [
                    new Length([
                        'min' => 0,
                        'minMessage' => 'The shipping cost cannot be short than 0 characters',
                    ])
                ]
            ])
            ->add('amount', NumberType::class, [
                'constraints' => [
                    new Length([
                        'min' => 0,
                        'minMessage' => 'The shipping cost cannot be short than 0 characters',
                    ])
                ]
            ])
            ->add('paymentMethod', TextType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'The payment method can not be null',
                    ]),
                    new Length([
                        'max' => 20,
                        'maxMessage' => 'The payment method cannot be longer than 20 characters',
                        'min' => 3,
                        'minMessage' => 'The payment method cannot be short than 3 characters',
                    ])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseOrder::class,
        ]);
    }
}
