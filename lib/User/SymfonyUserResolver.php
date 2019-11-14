<?php

namespace Noop\FlushLog\User;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SymfonyUserResolver implements UserResolverInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function resolveUserId()
    {
        if ($token = $this->tokenStorage->getToken()) {
            if ($user = $token->getUser()) {
                if (is_string($user)) {
                    return null;
                }

                if (is_object($user) && method_exists($user, 'getId')) {
                    return $user->getId();
                }
            }
        }

        return null;
    }

    public function resolveUsername()
    {
        if ($token = $this->tokenStorage->getToken()) {
            if ($user = $token->getUser()) {
                if (is_string($user)) {
                    return $user;
                }

                if ($user instanceof UserInterface) {
                    return $user->getUsername();
                }
            }
        }

        return null;
    }
}
