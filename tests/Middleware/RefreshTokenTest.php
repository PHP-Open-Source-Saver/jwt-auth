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

namespace PHPOpenSourceSaver\JWTAuth\Test\Middleware;

use Illuminate\Http\Response;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class RefreshTokenTest extends AbstractMiddleware
{
    /**
     * @var RefreshToken
     */
    protected $middleware;

    public function setUp(): void
    {
        parent::setUp();

        $this->middleware = new RefreshToken($this->auth);
    }

    public function testItShouldRefreshAToken()
    {
        $parser = \Mockery::mock(Parser::class);
        $parser->shouldReceive('hasToken')->once()->andReturn(true);

        $this->auth->shouldReceive('parser')->andReturn($parser);

        $this->auth->parser()->shouldReceive('setRequest')->once()->with($this->request)->andReturn($this->auth->parser());
        $this->auth->shouldReceive('parseToken->refresh')->once()->andReturn('foo.bar.baz');

        $response = $this->middleware->handle($this->request, function () {
            return new Response();
        });

        $this->assertSame($response->headers->get('authorization'), 'Bearer foo.bar.baz');
    }

    public function testItShouldThrowAnUnauthorizedExceptionIfTokenNotProvided()
    {
        $this->expectException(UnauthorizedHttpException::class);

        $parser = \Mockery::mock(Parser::class);
        $parser->shouldReceive('hasToken')->once()->andReturn(false);

        $this->auth->shouldReceive('parser')->andReturn($parser);
        $this->auth->parser()->shouldReceive('setRequest')->once()->with($this->request)->andReturn($this->auth->parser());

        $this->middleware->handle($this->request, function () {
        });
    }

    public function testItShouldThrowAnUnauthorizedExceptionIfTokenInvalid()
    {
        $this->expectException(UnauthorizedHttpException::class);

        $parser = \Mockery::mock(Parser::class);
        $parser->shouldReceive('hasToken')->once()->andReturn(true);

        $this->auth->shouldReceive('parser')->andReturn($parser);

        $this->auth->parser()->shouldReceive('setRequest')->once()->with($this->request)->andReturn($this->auth->parser());
        $this->auth->shouldReceive('parseToken->refresh')->once()->andThrow(new TokenInvalidException());

        $this->middleware->handle($this->request, function () {
        });
    }
}
