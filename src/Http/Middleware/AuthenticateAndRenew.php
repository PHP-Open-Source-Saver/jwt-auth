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

namespace PHPOpenSourceSaver\JWTAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AuthenticateAndRenew extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     *
     * @return mixed
     *
     * @throws UnauthorizedHttpException
     */
    public function handle($request, Closure $next)
    {
        $this->authenticate($request);

        $response = $next($request);

        // Send the refreshed token back to the client.
        return $this->setAuthenticationHeader($response);
    }
}
