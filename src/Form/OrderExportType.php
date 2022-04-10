<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Regex;

class OrderExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fileName', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9]{0,30}$/',
                        'message' => 'CSV file name is incorrect.'
                    ]),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'choices'  => [
                    'Pending' => 1,
                    'Approved' => 2,
                    'Canceled' => 3,
                    'Completed' => 4
                ],
            ])
            ->add('fromDate', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'constraints' => [
                    new LessThanOrEqual([
                        'value' => new \DateTime("today"),
                        'message' => 'Date (from) cannot be greater than today.',
                    ]),
                ],
            ])
            ->add('toDate', DateType::class, [
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                if ($form->isSubmitted() && $form->isValid()) {
                    $data = $event->getData();
                    if ($data['toDate'] < $data['fromDate']) {
                        $form->get('toDate')->addError(new FormError('The specified timestamp is not valid.'));
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
        ]);
    }
}
