<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) 2014-2021 Sean Tymon <tymon148@gmail.com>
 * (c) 2021 PHP Open Source Saver
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPOpenSourceSaver\JWTAuth\Test\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class LaravelUserStub extends UserStub implements Authenticatable, JWTSubject
{
    public function getAuthIdentifierName()
    {
    }

    public function getAuthIdentifier()
    {
    }

    public function getAuthPasswordName()
    {
    }

    public function getAuthPassword()
    {
    }

    public function getRememberToken()
    {
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
    }
}
