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

namespace PHPOpenSourceSaver\JWTAuth\Support;

trait CustomClaims
{
    /**
     * Custom claims.
     */
    protected array $customClaims = [];

    /**
     * Set the custom claims.
     */
    public function setCustomClaims(array $customClaims): self
    {
        $this->customClaims = $customClaims;

        return $this;
    }

    /**
     * Get the custom claims.
     */
    public function getCustomClaims(): array
    {
        return $this->customClaims;
    }

    /**
     * Alias of setCustomClaims.
     * @deprecated Please use setCustomClaims(array)
     */
    public function customClaims(array $customClaims): self
    {
        return $this->setCustomClaims($customClaims);
    }

    /**
     * Alias of setCustomClaims.
     * @deprecated Please use setCustomClaims(array)
     */
    public function claims(array $customClaims): self
    {
        return $this->setCustomClaims($customClaims);
    }
}
