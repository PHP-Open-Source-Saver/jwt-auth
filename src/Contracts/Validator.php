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

namespace PHPOpenSourceSaver\JWTAuth\Contracts;

interface Validator
{
    /**
     * Perform some checks on the value.
     *
     * @return void
     */
    public function check($value);

    /**
     * Helper function to return a boolean.
     *
     * @param array $value
     *
     * @return bool
     */
    public function isValid($value);
}
