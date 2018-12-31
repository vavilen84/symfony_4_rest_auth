<?php

namespace App\EntityListener;

use App\Entity\User;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserListener
{
    protected $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function prePersist(User $user)
    {
        $this->hashPassword($user);
    }

    public function preUpdate(User $user, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField('password')) {
            $this->hashPassword($user);
        }
    }

    protected function hashPassword(User $user)
    {
        $password = $user->getPassword();
        $encodedPassword = $this->encoder->encodePassword($user, $password);
        $user->setPassword($encodedPassword);
    }
}
