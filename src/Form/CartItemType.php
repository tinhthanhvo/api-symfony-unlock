<?php

namespace App\Form;

use App\Entity\Cart;
use App\Entity\ProductItem;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class CartItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('productItem', EntityType::class, [
                'label' => 'Product item',
                'class' => ProductItem::class,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Product item cannot be null.',
                    ])
                ]
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Amount',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Amount of item cannot be null.',
                    ]),
                    new Positive([
                        'message' => 'Amount of item have to be a positive number.',
                    ])
                ]
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Total price cannot be null.',
                    ]),
                    new Positive([
                        'message' => 'Total price have to be a positive number.',
                    ])
                ]
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                if ($form->isSubmitted() && $form->isValid()) {
                    $data = $event->getData();
                    if ($data->getProductItem()->getAmount() < $data->getAmount()) {
                        $form->get('amount')->addError(new FormError('Exceeding the number of existences.'));
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Cart::class,
            'csrf_protection' => false,
        ]);
    }
}
