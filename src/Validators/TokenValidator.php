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

namespace PHPOpenSourceSaver\JWTAuth\Validators;

use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class TokenValidator extends Validator
{
    /**
     * Check the structure of the token.
     *
     * @param string $value
     *
     * @return string
     *
     * @throws TokenInvalidException
     */
    public function check($value)
    {
        return $this->validateStructure($value);
    }

    /**
     * @param string $token
     *
     * @return string
     *
     * @throws TokenInvalidException
     */
    protected function validateStructure($token)
    {
        $parts = explode('.', $token);

        if (3 !== count($parts)) {
            throw new TokenInvalidException('Wrong number of segments');
        }

        $parts = array_filter(array_map('trim', $parts));

        if (3 !== count($parts) || implode('.', $parts) !== $token) {
            throw new TokenInvalidException('Malformed token');
        }

        return $token;
    }
}
