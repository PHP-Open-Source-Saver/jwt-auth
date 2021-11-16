<?php

/*
 * This file is part of jwt-auth.
 *
 * (c) Sean Tymon <tymon148@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPOpenSourceSaver\JWTAuth\Http\Parser;

use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Contracts\Http\Parser as ParserContract;

class QueryString implements ParserContract
{
    use KeyTrait;

    /**
     * Try to parse the token from the request query string.
     *
     * @param Request $request
     *
     * @return null|string
     */
    public function parse(Request $request)
    {
        return $request->query($this->key);
    }
}
