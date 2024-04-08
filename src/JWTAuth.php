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

namespace PHPOpenSourceSaver\JWTAuth;

use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Auth;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser;

class JWTAuth extends JWT
{
    /**
     * The authentication provider.
     *
     * @var Auth
     */
    protected $auth;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(Manager $manager, Auth $auth, Parser $parser)
    {
        parent::__construct($manager, $parser);
        $this->auth = $auth;
    }

    /**
     * Attempt to authenticate the user and return the token.
     *
     * @return false|string
     */
    public function attempt(array $credentials)
    {
        if (!$this->auth->byCredentials($credentials)) {
            return false;
        }

        return $this->fromUser($this->user());
    }

    /**
     * Authenticate a user via a token.
     *
     * @return JWTSubject|false
     */
    public function authenticate()
    {
        $id = $this->getPayload()->get('sub') ?: $this->getPayload()->get('id');

        if (!$this->auth->byId($id)) {
            return false;
        }

        return $this->user();
    }

    /**
     * Alias for authenticate().
     *
     * @return JWTSubject|false
     */
    public function toUser()
    {
        return $this->authenticate();
    }

    /**
     * Get the authenticated user.
     *
     * @return JWTSubject
     */
    public function user()
    {
        return $this->auth->user();
    }
}
