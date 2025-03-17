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

trait RefreshFlow
{
    /**
     * The refresh flow flag.
     */
    protected bool $refreshFlow = false;

    /**
     * Set the refresh flow flag.
     */
    public function setRefreshFlow(bool $refreshFlow = true): static
    {
        $this->refreshFlow = $refreshFlow;

        return $this;
    }
}
