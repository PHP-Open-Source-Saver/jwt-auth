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
use Illuminate\Support\Arr;

class LumenRouteParams extends RouteParams
{
    /**
     * Try to get the token from the route parameters.
     *
     * @return string|null
     */
    public function parse(Request $request)
    {
        // WARNING: Only use this parser if you know what you're doing!
        // It will only work with poorly-specified aspects of certain Lumen releases.
        // Route is the expected kind of array, and has a parameter with the key we want.
        return Arr::get($request->route(), '2.'.$this->key);
    }
}
