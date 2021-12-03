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

namespace PHPOpenSourceSaver\JWTAuth\Providers\Auth;

use Illuminate\Contracts\Auth\Guard as GuardContract;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Auth;

class Illuminate implements Auth
{
    /**
     * The authentication guard.
     *
     * @var GuardContract
     */
    protected $auth;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(GuardContract $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Check a user's credentials.
     *
     * @return bool
     */
    public function byCredentials(array $credentials)
    {
        return $this->auth->once($credentials);
    }

    /**
     * Authenticate a user via the id.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function byId($id)
    {
        return $this->auth->onceUsingId($id);
    }

    /**
     * Get the currently authenticated user.
     *
     * @return mixed
     */
    public function user()
    {
        return $this->auth->user();
    }
}
