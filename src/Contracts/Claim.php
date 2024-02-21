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

use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;

interface Claim
{
    /**
     * Set the claim value, and call a validate method.
     *
     * @return $this
     *
     * @throws InvalidClaimException
     */
    public function setValue($value);

    /**
     * Get the claim value.
     */
    public function getValue();

    /**
     * Set the claim name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * Get the claim name.
     *
     * @return string
     */
    public function getName();

    /**
     * Validate the Claim value.
     *
     * @return bool
     */
    public function validateCreate($value);
}
