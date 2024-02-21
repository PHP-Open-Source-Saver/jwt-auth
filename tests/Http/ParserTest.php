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

namespace PHPOpenSourceSaver\JWTAuth\Test\Http;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Crypt;
use PHPOpenSourceSaver\JWTAuth\Contracts\Http\Parser as ParserContract;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\AuthHeaders;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Cookies;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\InputSource;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\LumenRouteParams;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\QueryString;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\RouteParams;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;

class ParserTest extends AbstractTestCase
{
    /** @test */
    public function itShouldReturnTheTokenFromTheAuthorizationHeader()
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('Authorization', 'Bearer foobar');

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            new AuthHeaders(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromThePrefixedAuthenticationHeader()
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('Authorization', 'Custom foobar');

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            (new AuthHeaders())->setHeaderPrefix('Custom'),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromTheCustomAuthenticationHeader()
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('custom_authorization', 'Bearer foobar');

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            (new AuthHeaders())->setHeaderName('custom_authorization'),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromTheAltAuthorizationHeaders()
    {
        $request1 = Request::create('foo', 'POST');
        $request1->server->set('HTTP_AUTHORIZATION', 'Bearer foobar');

        $request2 = Request::create('foo', 'POST');
        $request2->server->set('REDIRECT_HTTP_AUTHORIZATION', 'Bearer foobarbaz');

        $parser = new Parser($request1, [
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());

        $parser->setRequest($request2);
        $this->assertSame($parser->parseToken(), 'foobarbaz');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldNotStripTrailingHyphensFromTheAuthorizationHeader()
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('Authorization', 'Bearer foobar--');

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            new AuthHeaders(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar--');
        $this->assertTrue($parser->hasToken());
    }

    /**
     * @test
     *
     * @dataProvider whitespaceProvider
     */
    public function itShouldHandleExcessWhitespaceFromTheAuthorizationHeader($whitespace)
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('Authorization', "Bearer{$whitespace}foobar{$whitespace}");

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            new AuthHeaders(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    public function whitespaceProvider()
    {
        return [
            'space' => [' '],
            'multiple spaces' => ['    '],
            'tab' => ["\t"],
            'multiple tabs' => ["\t\t\t"],
            'new line' => ["\n"],
            'multiple new lines' => ["\n\n\n"],
            'carriage return' => ["\r"],
            'carriage returns' => ["\r\r\r"],
            'mixture of whitespace' => ["\t \n \r \t \n"],
        ];
    }

    /** @test */
    public function itShouldReturnTheTokenFromQueryString()
    {
        $request = Request::create('foo', 'GET', ['token' => 'foobar']);

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromTheCustomQueryString()
    {
        $request = Request::create('foo', 'GET', ['custom_token_key' => 'foobar']);

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            (new QueryString())->setKey('custom_token_key'),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromTheQueryStringNotTheInputSource()
    {
        $request = Request::create('foo?token=foobar', 'POST', [], [], [], [], json_encode(['token' => 'foobarbaz']));

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromTheCustomQueryStringNotTheCustomInputSource()
    {
        $request = Request::create('foo?custom_token_key=foobar', 'POST', [], [], [], [], json_encode(['custom_token_key' => 'foobarbaz']));

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            (new QueryString())->setKey('custom_token_key'),
            (new InputSource())->setKey('custom_token_key'),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromInputSource()
    {
        $request = Request::create('foo', 'POST', [], [], [], [], json_encode(['token' => 'foobar']));
        $request->headers->set('Content-Type', 'application/json');

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromTheCustomInputSource()
    {
        $request = Request::create('foo', 'POST', [], [], [], [], json_encode(['custom_token_key' => 'foobar']));
        $request->headers->set('Content-Type', 'application/json');

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            (new InputSource())->setKey('custom_token_key'),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromAnUnencryptedCookie()
    {
        $request = Request::create('foo', 'POST', [], ['token' => 'foobar']);

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
            new Cookies(false),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnTheTokenFromACryptedCookie()
    {
        Crypt::shouldReceive('encrypt')
            ->with('foobar')
            ->once()
            ->andReturn('cryptedFoobar');

        $request = Request::create('foo', 'POST', [], ['token' => Crypt::encrypt('foobar')]);

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
            new Cookies(true),
        ]);

        Crypt::shouldReceive('decrypt')
            ->with('cryptedFoobar')
            ->times(2)
            ->andReturn('foobar');

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldThrowTokenInvalidExceptionFromAInvalidEncryptedCookie()
    {
        $request = Request::create('foo', 'POST', [], ['token' => 'foobar']);

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
            new Cookies(true),
        ]);

        Crypt::shouldReceive('decrypt')
            ->with('foobar')
            ->andThrow(new DecryptException());

        $this->expectException(TokenInvalidException::class);

        $parser->parseToken();
    }

    /** @test */
    public function itShouldReturnTheTokenFromRoute()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);
        $request->setRouteResolver(fn () => $this->getRouteMock('foobar'));

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    protected function getRouteMock($expectedParameterValue = null, $expectedParameterName = 'token')
    {
        return \Mockery::mock(Route::class)
            ->shouldReceive('parameter')
            ->with($expectedParameterName)
            ->andReturn($expectedParameterValue)
            ->getMock();
    }

    /** @test */
    public function itShouldReturnTheTokenFromRouteWithACustomParam()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);
        $request->setRouteResolver(fn () => $this->getRouteMock('foobar', 'custom_route_param'));

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            (new RouteParams())->setKey('custom_route_param'),
        ]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldIgnoreRoutelessRequests()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);
        $request->setRouteResolver(function () {
        });

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertNull($parser->parseToken());
        $this->assertFalse($parser->hasToken());
    }

    /** @test */
    public function itShouldIgnoreLumenRequestArrays()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);
        $request->setRouteResolver(fn () => [false, ['uses' => 'someController'], ['token' => 'foobar']]);

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertNull($parser->parseToken());
        $this->assertFalse($parser->hasToken());
    }

    /** @test */
    public function itShouldAcceptLumenRequestArraysWithSpecialClass()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);
        $request->setRouteResolver(fn () => [false, ['uses' => 'someController'], ['token' => 'foo.bar.baz']]);

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new LumenRouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'foo.bar.baz');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldReturnNullIfNoTokenInRequest()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);
        $request->setRouteResolver(fn () => $this->getRouteMock());

        $parser = new Parser($request);
        $parser->setChain([
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ]);

        $this->assertNull($parser->parseToken());
        $this->assertFalse($parser->hasToken());
    }

    /** @test */
    public function itShouldRetrieveTheChain()
    {
        $chain = [
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ];

        $parser = new Parser(\Mockery::mock(Request::class));
        $parser->setChain($chain);

        $this->assertSame($parser->getChain(), $chain);
    }

    /** @test */
    public function itShouldRetrieveTheChainWithAlias()
    {
        $chain = [
            new AuthHeaders(),
            new QueryString(),
            new InputSource(),
            new RouteParams(),
        ];

        /* @var Request $request */
        $request = \Mockery::mock(Request::class);

        $parser = new Parser($request);
        $parser->setChainOrder($chain);

        $this->assertSame($parser->getChain(), $chain);
    }

    /** @test */
    public function itShouldSetTheCookieKey()
    {
        $cookies = (new Cookies())->setKey('test');
        $this->assertInstanceOf(Cookies::class, $cookies);
    }

    /** @test */
    public function itShouldAddCustomParser()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);

        $customParser = \Mockery::mock(ParserContract::class);
        $customParser->shouldReceive('parse')->with($request)->andReturn('foobar');

        $parser = new Parser($request);
        $parser->addParser($customParser);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldAddMultipleCustomParser()
    {
        $request = Request::create('foo', 'GET', ['foo' => 'bar']);

        $customParser1 = \Mockery::mock(ParserContract::class);
        $customParser1->shouldReceive('parse')->with($request)->andReturn(false);

        $customParser2 = \Mockery::mock(ParserContract::class);
        $customParser2->shouldReceive('parse')->with($request)->andReturn('foobar');

        $parser = new Parser($request);
        $parser->addParser([$customParser1, $customParser2]);

        $this->assertSame($parser->parseToken(), 'foobar');
        $this->assertTrue($parser->hasToken());
    }

    /** @test */
    public function itShouldIgnoreNonBearerTokens()
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('Authorization', 'Basic OnBhc3N3b3Jk');

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            new AuthHeaders(),
            new RouteParams(),
        ]);

        $this->assertNull($parser->parseToken());
        $this->assertFalse($parser->hasToken());
    }

    /** @test */
    public function itShouldIgnoreTokensWithoutPrefixes()
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5');

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            new AuthHeaders(),
            new RouteParams(),
        ]);

        $this->assertNull($parser->parseToken());
        $this->assertFalse($parser->hasToken());
    }

    /** @test */
    public function itShouldParseMultipleAuthHeaders()
    {
        $request = Request::create('foo', 'POST');
        $request->headers->set('Authorization', 'Bearer eyJhbGciOiJIUzI1NiIsInR5, Basic OnBhc3N3b3Jk');

        $parser = new Parser($request);

        $parser->setChain([
            new QueryString(),
            new InputSource(),
            new AuthHeaders(),
            new RouteParams(),
        ]);

        $this->assertSame($parser->parseToken(), 'eyJhbGciOiJIUzI1NiIsInR5');
        $this->assertTrue($parser->hasToken());
    }
}
