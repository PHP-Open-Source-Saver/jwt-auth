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

namespace PHPOpenSourceSaver\JWTAuth\Contracts\Providers;

interface Auth
{
    /**
     * Check a user's credentials.
     */
    public function byCredentials(array $credentials);

    /**
     * Authenticate a user via the id.
     */
    public function byId($id);

    /**
     * Get the currently authenticated user.
     */
    public function user();
}
