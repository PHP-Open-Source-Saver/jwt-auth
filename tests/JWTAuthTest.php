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

namespace PHPOpenSourceSaver\JWTAuth\Test;

use Illuminate\Http\Request;
use Mockery\LegacyMockInterface;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Factory;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Manager;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub;
use PHPOpenSourceSaver\JWTAuth\Token;

class JWTAuthTest extends AbstractTestCase
{
    protected LegacyMockInterface $manager;

    protected LegacyMockInterface $auth;

    protected LegacyMockInterface $parser;

    protected JWTAuth $jwtAuth;

    public function setUp(): void
    {
        $this->manager = \Mockery::mock(Manager::class);
        $this->auth = \Mockery::mock(Auth::class);
        $this->parser = \Mockery::mock(Parser::class);
        $this->jwtAuth = new JWTAuth($this->manager, $this->auth, $this->parser);
    }

    public function testItShouldReturnATokenWhenPassingAUser()
    {
        $payloadFactory = \Mockery::mock(Factory::class);
        $payloadFactory->shouldReceive('make')->andReturn(\Mockery::mock(Payload::class));

        $this->manager
            ->shouldReceive('getPayloadFactory->customClaims')
            ->once()
            ->with(['sub' => 1, 'prv' => sha1('PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub'), 'foo' => 'bar', 'role' => 'admin'])
            ->andReturn($payloadFactory);

        $this->manager->shouldReceive('encode->get')->once()->andReturn('foo.bar.baz');

        $token = $this->jwtAuth->fromUser(new UserStub());

        $this->assertSame($token, 'foo.bar.baz');
    }

    public function testItShouldPassProviderCheckIfHashMatches()
    {
        $payloadFactory = \Mockery::mock(Factory::class);
        $payloadFactory->shouldReceive('make')->andReturn(\Mockery::mock(Payload::class));
        $payloadFactory->shouldReceive('get')
            ->with('prv')
            ->andReturn(sha1('PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub'));

        $this->manager->shouldReceive('decode')->once()->andReturn($payloadFactory);

        $this->assertTrue($this->jwtAuth->setToken('foo.bar.baz')->checkSubjectModel('PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub'));
    }

    public function testItShouldPassProviderCheckIfHashMatchesWhenProviderIsNull()
    {
        $payloadFactory = \Mockery::mock(Factory::class);
        $payloadFactory->shouldReceive('make')->andReturn(\Mockery::mock(Payload::class));
        $payloadFactory->shouldReceive('get')
            ->with('prv')
            ->andReturnNull();

        $this->manager->shouldReceive('decode')->once()->andReturn($payloadFactory);

        $this->assertTrue($this->jwtAuth->setToken('foo.bar.baz')->checkSubjectModel('PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub'));
    }

    public function testItShouldNotPassProviderCheckIfHashNotMatch()
    {
        $payloadFactory = \Mockery::mock(Factory::class);
        $payloadFactory->shouldReceive('make')->andReturn(\Mockery::mock(Payload::class));
        $payloadFactory->shouldReceive('get')
            ->with('prv')
            ->andReturn(sha1('PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub1'));

        $this->manager->shouldReceive('decode')->once()->andReturn($payloadFactory);

        $this->assertFalse($this->jwtAuth->setToken('foo.bar.baz')->checkSubjectModel('PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub'));
    }

    public function testItShouldReturnATokenWhenPassingValidCredentialsToAttemptMethod()
    {
        $payloadFactory = \Mockery::mock(Factory::class);
        $payloadFactory->shouldReceive('make')->andReturn(\Mockery::mock(Payload::class));

        $this->manager
            ->shouldReceive('getPayloadFactory->customClaims')
            ->once()
            ->with(['sub' => 1, 'prv' => sha1('PHPOpenSourceSaver\JWTAuth\Test\Stubs\UserStub'), 'foo' => 'bar', 'role' => 'admin'])
            ->andReturn($payloadFactory);

        $this->manager->shouldReceive('encode->get')->once()->andReturn('foo.bar.baz');

        $this->auth->shouldReceive('byCredentials')->once()->andReturn(true);
        $this->auth->shouldReceive('user')->once()->andReturn(new UserStub());

        $token = $this->jwtAuth->attempt(['foo' => 'bar']);

        $this->assertSame($token, 'foo.bar.baz');
    }

    public function testItShouldReturnFalseWhenPassingInvalidCredentialsToAttemptMethod()
    {
        $this->manager->shouldReceive('encode->get')->never();
        $this->auth->shouldReceive('byCredentials')->once()->andReturn(false);
        $this->auth->shouldReceive('user')->never();

        $token = $this->jwtAuth->attempt(['foo' => 'bar']);

        $this->assertFalse($token);
    }

    public function testItShouldThrowAnExceptionWhenNotProvidingAToken()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('A token is required');

        $this->jwtAuth->toUser();
    }

    public function testItShouldReturnTheOwningUserFromATokenContainingAnExistingUser()
    {
        $payload = \Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->once()->with('sub')->andReturn(1);

        $this->manager->shouldReceive('decode')->once()->andReturn($payload);

        $this->auth->shouldReceive('byId')->once()->with(1)->andReturn(true);
        $this->auth->shouldReceive('user')->once()->andReturn((object) ['id' => 1]);

        $user = $this->jwtAuth->setToken('foo.bar.baz')->customClaims(['foo' => 'bar'])->authenticate();

        $this->assertSame($user->id, 1);
    }

    public function testItShouldReturnFalseWhenPassingATokenNotContainingAnExistingUser()
    {
        $payload = \Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->once()->with('sub')->andReturn(1);

        $this->manager->shouldReceive('decode')->once()->andReturn($payload);

        $this->auth->shouldReceive('byId')->once()->with(1)->andReturn(false);
        $this->auth->shouldReceive('user')->never();

        $user = $this->jwtAuth->setToken('foo.bar.baz')->authenticate();

        $this->assertFalse($user);
    }

    public function testItShouldRefreshAToken()
    {
        $newToken = \Mockery::mock(Token::class);
        $newToken->shouldReceive('get')->once()->andReturn('baz.bar.foo');

        $this->manager->shouldReceive('customClaims->refresh')->once()->andReturn($newToken);

        $result = $this->jwtAuth->setToken('foo.bar.baz')->refresh();

        $this->assertSame($result, 'baz.bar.foo');
    }

    public function testItShouldInvalidateAToken()
    {
        $token = new Token('foo.bar.baz');

        $this->manager->shouldReceive('invalidate')->once()->with($token, false)->andReturn(true);

        $this->jwtAuth->setToken($token)->invalidate();
    }

    public function testItShouldForceInvalidateATokenForever()
    {
        $token = new Token('foo.bar.baz');

        $this->manager->shouldReceive('invalidate')->once()->with($token, true)->andReturn(true);

        $this->jwtAuth->setToken($token)->invalidate(true);
    }

    public function testItShouldRetrieveTheTokenFromTheRequest()
    {
        $this->parser->shouldReceive('parseToken')->andReturn('foo.bar.baz');

        $this->assertInstanceOf(Token::class, $this->jwtAuth->parseToken()->getToken());
        $this->assertEquals($this->jwtAuth->getToken(), 'foo.bar.baz');
    }

    public function testItShouldGetTheAuthenticatedUser()
    {
        $manager = $this->jwtAuth->manager();
        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testItShouldReturnFalseIfTheTokenIsInvalid()
    {
        $this->parser->shouldReceive('parseToken')->andReturn('foo.bar.baz');
        $this->manager->shouldReceive('decode')->once()->andThrow(new TokenInvalidException());

        $this->assertFalse($this->jwtAuth->parseToken()->check());
    }

    public function testItShouldReturnTrueIfTheTokenIsValid()
    {
        $payload = \Mockery::mock(Payload::class);

        $this->parser->shouldReceive('parseToken')->andReturn('foo.bar.baz');
        $this->manager->shouldReceive('decode')->once()->andReturn($payload);

        $this->assertTrue($this->jwtAuth->parseToken()->check());
    }

    public function testItShouldThrowAnExceptionWhenTokenNotPresentInRequest()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('The token could not be parsed from the request');

        $this->parser->shouldReceive('parseToken')->andReturn(false);

        $this->jwtAuth->parseToken();
    }

    public function testItShouldReturnFalseWhenNoTokenIsSet()
    {
        $this->parser->shouldReceive('parseToken')->andReturn(false);

        $this->assertNull($this->jwtAuth->getToken());
    }

    public function testItShouldMagicallyCallTheManager()
    {
        $this->manager->shouldReceive('getBlacklist')->andReturn(new \stdClass());

        $blacklist = $this->jwtAuth->manager()->getBlacklist();

        $this->assertInstanceOf(\stdClass::class, $blacklist);
    }

    public function testItShouldSetTheRequest()
    {
        $request = Request::create('/foo', 'GET', ['token' => 'some.random.token']);

        $this->parser->shouldReceive('setRequest')->once()->with($request);
        $this->parser->shouldReceive('parseToken')->andReturn('some.random.token');

        $token = $this->jwtAuth->setRequest($request)->getToken();

        $this->assertEquals('some.random.token', $token);
    }

    public function testItShouldUnsetTheToken()
    {
        $this->parser->shouldReceive('parseToken')->andThrow(new JWTException());
        $token = new Token('foo.bar.baz');
        $this->jwtAuth->setToken($token);

        $this->assertSame($this->jwtAuth->getToken(), $token);
        $this->jwtAuth->unsetToken();
        $this->assertNull($this->jwtAuth->getToken());
    }

    public function testItShouldGetTheManagerInstance()
    {
        $manager = $this->jwtAuth->manager();
        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testItShouldGetTheParserInstance()
    {
        $parser = $this->jwtAuth->parser();
        $this->assertInstanceOf(Parser::class, $parser);
    }

    public function testItShouldGetAClaimValue()
    {
        $payload = \Mockery::mock(Payload::class);
        $payload->shouldReceive('get')->once()->with('sub')->andReturn(1);

        $this->manager->shouldReceive('decode')->once()->andReturn($payload);

        $this->assertSame($this->jwtAuth->setToken('foo.bar.baz')->getClaim('sub'), 1);
    }
}
