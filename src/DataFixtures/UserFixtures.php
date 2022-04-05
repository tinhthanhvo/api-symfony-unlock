<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager, array $role = ['ROLE_ADMIN']): void
    {
        $user = new User();
        $user->setFullName('Full name');
        $user->setEmail('user@gmail.com');
        $user->setPassword('Abc123');
        $user->setPhoneNumber('0123456789');
        $user->setRoles($role);
        $user->setCreateAt(new \DateTime("now"));

        $manager->persist($user);
        $manager->flush();
    }
}
