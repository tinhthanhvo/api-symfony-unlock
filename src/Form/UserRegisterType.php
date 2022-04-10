<?php

namespace App\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserRegisterType extends AbstractType
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full name',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Full name cannot be null.',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Full name cannot be longer than 150 characters.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Email cannot be null.',
                    ]),
                    new Length([
                        'max' => 180,
                        'maxMessage' => 'Email cannot be longer than 180 characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                        'message' => 'Email is incorrect.'
                    ]),
                ],
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone number',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Phone number cannot be null.',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{10,11}$/',
                        'message' => 'Phone number is incorrect.'
                    ]),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Password cannot be null.',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,20}$/',
                        'message' => 'Password is incorrect.'
                    ]),
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                if ($form->isSubmitted() && $form->isValid()) {
                    $user = $this->userRepository->findOneBy(['email' => $event->getData()->getEmail()]);
                    if ($user) {
                        $form->get('email')->addError(new FormError('Email is already existed.'));
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
        ]);
    }
}
