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

     * @throws InvalidClaimException
     */
    public function setValue(mixed $value): self;

    /**
     * Get the claim value.
     */
    public function getValue(): mixed;

    /**
     * Set the claim name.
     */
    public function setName(string $name): self;

    /**
     * Get the claim name.
     */
    public function getName(): string;

    /**
     * Validate the Claim value, and return it
     */
    public function validateCreate(mixed $value): mixed;
}
