<?php

namespace Indir\Base;

class Account
{
    public function __construct($user, $pass)
    {
        if (empty($user) || empty($pass)) {
            throw new \InvalidArgumentException('user and pass must not be empty.');
        }

        $this->user = $user;
        $this->pass = $pass;
    }

    public function __toString()
    {
        return "{$this->user}:{$this->pass}";
    }
}
