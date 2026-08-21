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

use PHPOpenSourceSaver\JWTAuth\Validators\TokenValidator;

class Token
{
    private string $value;

    /**
     * Create a new JSON Web Token.
     *
     * @throws Exceptions\TokenInvalidException
     */
    public function __construct(string $value)
    {
        $this->value = (new TokenValidator())->check($value);
    }

    /**
     * Get the token as string.
     */
    public function get(): string
    {
        return $this->value;
    }

    /**
     * Get the token when casting to string.
     */
    public function __toString(): string
    {
        return $this->get();
    }
}
