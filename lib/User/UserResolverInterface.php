<?php

namespace Noop\FlushLog\User;

interface UserResolverInterface
{
    public function resolveUserId();
    public function resolveUsername();
}
