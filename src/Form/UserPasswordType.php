<?php

namespace App\Form;

use App\Service\GetUserInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserPasswordType extends AbstractType
{
    private $encoder;
    private $userLogin;

    public function __construct(
        GetUserInfo $userLogin,
        UserPasswordHasherInterface $encoder
    ) {
        $this->userLogin = $userLogin;
        $this->encoder = $encoder;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('oldPassword', PasswordType::class, [
                'label' => 'Current password',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Current password cannot be null.',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,20}$/',
                        'message' => 'Current password is incorrect.'
                    ]),
                ],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'New password',
                'constraints' => [
                    new NotBlank([
                        'message' => 'New password cannot be null.',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{6,20}$/',
                        'message' => 'New password is incorrect.'
                    ]),
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                if ($form->isSubmitted() && $form->isValid()) {
                    if (
                        !$this->userLogin->isPasswordEqual(
                            $this->encoder,
                            $this->userLogin->getUserLoginInfo(),
                            $event->getData()['oldPassword']
                        )
                    ) {
                        $form->get('oldPassword')->addError(new FormError('Current password is wrong.'));
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
