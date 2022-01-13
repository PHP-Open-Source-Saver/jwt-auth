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

namespace PHPOpenSourceSaver\JWTAuth\Http\Parser;

use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Contracts\Http\Parser as ParserContract;

class RouteParams implements ParserContract
{
    use KeyTrait;

    /**
     * Try to get the token from the route parameters.
     *
     * @return string|null
     */
    public function parse(Request $request)
    {
        $route = $request->route();

        // Route may not be an instance of Illuminate\Routing\Route
        // (it's an array in Lumen <5.2) or not exist at all
        // (if the request was never dispatched)
        if (is_callable([$route, 'parameter'])) {
            return $route->parameter($this->key);
        }
    }
}
