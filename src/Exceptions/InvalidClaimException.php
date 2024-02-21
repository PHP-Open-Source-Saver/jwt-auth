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

namespace PHPOpenSourceSaver\JWTAuth\Exceptions;

use PHPOpenSourceSaver\JWTAuth\Claims\Claim;

class InvalidClaimException extends JWTException
{
    /**
     * Constructor.
     *
     * @param int $code
     *
     * @return void
     */
    public function __construct(Claim $claim, $code = 0, ?\Exception $previous = null)
    {
        parent::__construct('Invalid value provided for claim ['.$claim->getName().']', $code, $previous);
    }
}
