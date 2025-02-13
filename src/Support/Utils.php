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

use Carbon\Carbon;

class Utils
{
    /**
     * Get the Carbon instance for the current time, in the UTC timezone
     */
    public static function now(): Carbon
    {
        return Carbon::now('UTC');
    }

    /**
     * Get the Carbon instance for a unix timestamp, in UTC
     */
    public static function timestamp(int $timestamp): Carbon
    {
        return Carbon::createFromTimestampUTC($timestamp)->timezone('UTC');
    }

    /**
     * Checks if a unix timestamp is in the past.
     */
    public static function isPast(int $timestamp, int $leeway = 0): bool
    {
        $timestamp = static::timestamp($timestamp);

        return $leeway > 0
            ? $timestamp->addSeconds($leeway)->isPast()
            : $timestamp->isPast();
    }

    /**
     * Checks if a unix timestamp is in the future.
     */
    public static function isFuture(int $timestamp, int $leeway = 0): bool
    {
        $timestamp = static::timestamp($timestamp);

        return $leeway > 0
            ? $timestamp->subSeconds($leeway)->isFuture()
            : $timestamp->isFuture();
    }
}
