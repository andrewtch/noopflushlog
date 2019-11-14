<?php

namespace Noop\FlushLog\User;

class TestUserResolver implements UserResolverInterface
{
    public function resolveUserId()
    {
        return 1;
    }

    public function resolveUsername()
    {
        return 'test user';
    }
}
