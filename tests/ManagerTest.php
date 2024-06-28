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

use Illuminate\Support\Carbon;
use Mockery\LegacyMockInterface;
use PHPOpenSourceSaver\JWTAuth\Blacklist;
use PHPOpenSourceSaver\JWTAuth\Claims\Collection;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Claims\IssuedAt;
use PHPOpenSourceSaver\JWTAuth\Claims\Issuer;
use PHPOpenSourceSaver\JWTAuth\Claims\JwtId;
use PHPOpenSourceSaver\JWTAuth\Claims\NotBefore;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\JWT;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Factory;
use PHPOpenSourceSaver\JWTAuth\Manager;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Token;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;

class ManagerTest extends AbstractTestCase
{
    protected LegacyMockInterface $jwt;

    protected LegacyMockInterface $blacklist;

    protected LegacyMockInterface $factory;

    protected Manager $manager;

    protected LegacyMockInterface $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->jwt = \Mockery::mock(JWT::class);
        $this->blacklist = \Mockery::mock(Blacklist::class);
        $this->factory = \Mockery::mock(Factory::class);
        $this->manager = new Manager($this->jwt, $this->blacklist, $this->factory);
        $this->validator = \Mockery::mock(PayloadValidator::class);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldEncodeAPayload()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);
        $payload = new Payload($collection, $this->validator);

        $this->jwt->shouldReceive('encode')->with($payload->toArray())->andReturn('foo.bar.baz');

        $token = $this->manager->encode($payload);

        $this->assertEquals($token, 'foo.bar.baz');
    }

    /**
     * @throws InvalidClaimException|TokenBlacklistedException
     */
    public function testItShouldDecodeAToken()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);
        $payload = new Payload($collection, $this->validator);

        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());

        $this->factory->shouldReceive('setRefreshFlow')->andReturn($this->factory);
        $this->factory->shouldReceive('customClaims')->andReturn($this->factory);
        $this->factory->shouldReceive('make')->andReturn($payload);

        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(false);

        $payload = $this->manager->decode($token);

        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertSame($payload->count(), 6);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldThrowExceptionWhenTokenIsBlacklisted()
    {
        $this->expectException(TokenBlacklistedException::class);
        $this->expectExceptionMessage('The token has been blacklisted');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);
        $payload = new Payload($collection, $this->validator);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());

        $this->factory->shouldReceive('setRefreshFlow')->andReturn($this->factory);
        $this->factory->shouldReceive('customClaims')->with($payload->toArray())->andReturn($this->factory);
        $this->factory->shouldReceive('make')->andReturn($payload);

        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(true);

        $this->manager->decode($token);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldRefreshAToken()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp - 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);
        $payload = new Payload($collection, $this->validator);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->twice()->with('foo.bar.baz')->andReturn($payload->toArray());
        $this->jwt->shouldReceive('encode')->with($payload->toArray())->andReturn('baz.bar.foo');

        $this->factory->shouldReceive('setRefreshFlow')->with(true)->andReturn($this->factory);
        $this->factory->shouldReceive('customClaims')->andReturn($this->factory);
        $this->factory->shouldReceive('make')->andReturn($payload);

        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(false);
        $this->blacklist->shouldReceive('add')->once()->with($payload);

        $token = $this->manager->refresh($token);

        // $this->assertArrayHasKey('ref', $payload);
        $this->assertInstanceOf(Token::class, $token);
        $this->assertEquals('baz.bar.foo', $token);
    }

    public function testBuildRefreshClaimsMethodWillRefreshTheIAT()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp - 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);
        $payload = new Payload($collection, $this->validator);

        $managerClass = new \ReflectionClass(Manager::class);
        $buildRefreshClaimsMethod = $managerClass->getMethod('buildRefreshClaims');
        $buildRefreshClaimsMethod->setAccessible(true);
        $managerInstance = new Manager($this->jwt, $this->blacklist, $this->factory);

        $firstResult = $buildRefreshClaimsMethod->invokeArgs($managerInstance, [$payload]);
        Carbon::setTestNow(Carbon::now()->addMinutes(2));
        $secondResult = $buildRefreshClaimsMethod->invokeArgs($managerInstance, [$payload]);

        $this->assertIsInt($firstResult['iat']);
        $this->assertIsInt($secondResult['iat']);

        $carbonTimestamp = Carbon::createFromTimestamp($firstResult['iat']);
        $this->assertInstanceOf(Carbon::class, $carbonTimestamp);

        $carbonTimestamp = Carbon::createFromTimestamp($secondResult['iat']);
        $this->assertInstanceOf(Carbon::class, $carbonTimestamp);

        $this->assertNotEquals($firstResult['iat'], $secondResult['iat']);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldInvalidateAToken()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);
        $payload = new Payload($collection, $this->validator);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());

        $this->factory->shouldReceive('setRefreshFlow')->andReturn($this->factory);
        $this->factory->shouldReceive('customClaims')->with($payload->toArray())->andReturn($this->factory);
        $this->factory->shouldReceive('make')->andReturn($payload);

        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(false);

        $this->blacklist->shouldReceive('add')->with($payload)->andReturn(true);

        $this->manager->invalidate($token);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldForceInvalidateATokenForever()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);
        $payload = new Payload($collection, $this->validator);
        $token = new Token('foo.bar.baz');

        $this->jwt->shouldReceive('decode')->once()->with('foo.bar.baz')->andReturn($payload->toArray());

        $this->factory->shouldReceive('setRefreshFlow')->andReturn($this->factory);
        $this->factory->shouldReceive('customClaims')->with($payload->toArray())->andReturn($this->factory);
        $this->factory->shouldReceive('make')->andReturn($payload);

        $this->blacklist->shouldReceive('has')->with($payload)->andReturn(false);

        $this->blacklist->shouldReceive('addForever')->with($payload)->andReturn(true);

        $this->manager->invalidate($token, true);
    }

    public function testItShouldThrowAnExceptionWhenEnableBlacklistIsSetToFalse()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('You must have the blacklist enabled to invalidate a token.');

        $token = new Token('foo.bar.baz');

        $this->manager->setBlacklistEnabled(false)->invalidate($token);
    }

    public function testItShouldGetThePayloadFactory()
    {
        $this->assertInstanceOf(Factory::class, $this->manager->getPayloadFactory());
    }

    public function testItShouldGetTheJwtProvider()
    {
        $this->assertInstanceOf(JWT::class, $this->manager->getJWTProvider());
    }

    public function testItShouldGetTheBlacklist()
    {
        $this->assertInstanceOf(Blacklist::class, $this->manager->getBlacklist());
    }

    public function testIfShowBlacklistedExceptionConfigurationIsEnabled()
    {
        $this->manager->setBlackListExceptionEnabled(true);

        $this->assertIsBool($this->manager->getBlackListExceptionEnabled());
    }

    public function testIfBlackListedExceptionIsSetToTrue()
    {
        $this->manager->setBlackListExceptionEnabled(true);

        $this->assertTrue($this->manager->getBlackListExceptionEnabled());
    }

    public function testIfBlackListedExceptionIsSetToFalse()
    {
        $this->manager->setBlackListExceptionEnabled(false);

        $this->assertFalse($this->manager->getBlackListExceptionEnabled());
    }
}
