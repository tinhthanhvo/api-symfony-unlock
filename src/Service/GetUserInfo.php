<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class GetUserInfo
{
    /** @var  TokenStorageInterface */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return User|null
     */
    public function getUserLoginInfo()
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof TokenInterface) {
            /** @var User $user */
            $user = $token->getUser();

            return $user;
        }

        return null;
    }

    /**
     * @param UserPasswordHasherInterface $passwordHasher
     * @param UserInterface $user
     * @param string $compareString
     * @return bool
     */
    public function isPasswordEqual(
        UserPasswordHasherInterface $passwordHasher,
        UserInterface $user,
        string $compareString
    ): bool {
        if ($passwordHasher->isPasswordValid($user, $compareString)) {
            return true;
        }

        return false;
    }
}
