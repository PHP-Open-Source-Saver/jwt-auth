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

class AuthHeaders implements ParserContract
{
    /**
     * The header name.
     *
     * @var string
     */
    protected $header = 'authorization';

    /**
     * The header prefix.
     *
     * @var string
     */
    protected $prefix = 'bearer';

    /**
     * Attempt to parse the token from some other possible headers.
     *
     * @return string|null
     */
    protected function fromAltHeaders(Request $request)
    {
        return $request->server->get('HTTP_AUTHORIZATION') ?: $request->server->get('REDIRECT_HTTP_AUTHORIZATION');
    }

    /**
     * Try to parse the token from the request header.
     *
     * @return string|null
     */
    public function parse(Request $request)
    {
        $header = $request->headers->get($this->header) ?: $this->fromAltHeaders($request);

        if (null !== $header) {
            $position = strripos($header, $this->prefix);

            if (false !== $position) {
                $header = substr($header, $position + strlen($this->prefix));

                return trim(
                    false !== strpos($header, ',') ? strstr($header, ',', true) : $header
                );
            }
        }

        return null;
    }

    /**
     * Set the header name.
     *
     * @param string $headerName
     *
     * @return $this
     */
    public function setHeaderName($headerName)
    {
        $this->header = $headerName;

        return $this;
    }

    /**
     * Set the header prefix.
     *
     * @param string $headerPrefix
     *
     * @return $this
     */
    public function setHeaderPrefix($headerPrefix)
    {
        $this->prefix = $headerPrefix;

        return $this;
    }
}
