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
        $user->setPassword('$2y$13$ITJw4Lj7Sg4HQI1/lVRNNOyHKzjQ.J4LNclJ5MAoYO2c2FEMY0qVe');
        $user->setPhoneNumber('0123456789');
        $user->setRoles($role);

        $manager->persist($user);
        $manager->flush();
    }
}
