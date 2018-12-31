<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    const USERS = [
        1 => [
            'email' => 'user_1@example.com',
            'password' => 'securepassword'
        ]
    ];

    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);


        $manager->flush();
    }

    protected function loadUsers(ObjectManager $manager)
    {
        foreach (self::USERS as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($data['password']);
            $manager->persist($user);
        }
    }
}
