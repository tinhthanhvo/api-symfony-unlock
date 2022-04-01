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
                        'message' => 'fullName=Full name can not be null',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'fullName=Full name cannot be longer than 100 characters',
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank([
                        'message' => 'email=Email can not be null',
                    ]),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'email=Email cannot be longer than 150 characters',
                    ]),
                    new Regex([
                        'pattern' => '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix',
                        'message' => "email=Email is incorrect"
                    ])
                ]
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone number',
                'constraints' => [
                    new NotBlank([
                        'message' => 'phoneNumber=Phone number can not be null',
                    ]),
                    new Regex([
                        'pattern' => '/^[0-9]{10,20}$/',
                        'message' => "phoneNumber=Phone number is incorrect"
                    ])
                ]
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'constraints' => [
                    new NotBlank([
                        'message' => 'password=Password can not be null',
                    ]),
                    new Length([
                        'max' => 20, //255
                        'maxMessage' => 'password=Password cannot be longer than 20 characters',
                    ])
                ]
            ])
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                if (isset($data['email']) && !empty($data['email'])) {
                    $user = $this->userRepository->findOneBy([
                        'email' => $data['email'],
                    ]);
                    if ($user) {
                        $form = $event->getForm();
                        $form->addError(new FormError('email=Email is already existed'));
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
