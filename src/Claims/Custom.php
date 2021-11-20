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

namespace PHPOpenSourceSaver\JWTAuth\Claims;

use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;

class Custom extends Claim
{
    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     *
     * @throws InvalidClaimException
     */
    public function __construct($name, $value)
    {
        parent::__construct($value);
        $this->setName($name);
    }
}
