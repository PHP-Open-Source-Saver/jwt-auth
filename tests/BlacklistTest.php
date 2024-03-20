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

use Mockery\LegacyMockInterface;
use PHPOpenSourceSaver\JWTAuth\Blacklist;
use PHPOpenSourceSaver\JWTAuth\Claims\Collection;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Claims\IssuedAt;
use PHPOpenSourceSaver\JWTAuth\Claims\Issuer;
use PHPOpenSourceSaver\JWTAuth\Claims\JwtId;
use PHPOpenSourceSaver\JWTAuth\Claims\NotBefore;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Storage;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;
use PHPUnit\Framework\Attributes\DataProvider;

class BlacklistTest extends AbstractTestCase
{
    protected LegacyMockInterface $storage;

    protected Blacklist $blacklist;

    protected LegacyMockInterface $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->storage = \Mockery::mock(Storage::class);
        $this->blacklist = new Blacklist($this->storage);
        $this->validator = \Mockery::mock(PayloadValidator::class);
    }

    public function testItShouldAddAValidTokenToTheBlacklist()
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

        $refreshTTL = 20161;

        $this->storage->shouldReceive('get')
            ->with('foo')
            ->once()
            ->andReturn([]);

        $this->storage->shouldReceive('add')
            ->with('foo', ['valid_until' => $this->testNowTimestamp], $refreshTTL + 1)
            ->once();

        $this->blacklist->setRefreshTTL($refreshTTL)->add($payload);
    }

    public function testItShouldAddATokenWithNoExpToTheBlacklistForever()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = new Payload($collection, $this->validator);

        $this->storage->shouldReceive('forever')->with('foo', 'forever')->once();
        $this->blacklist->add($payload);
    }

    public function testItShouldReturnTrueWhenAddingAnExpiredTokenToTheBlacklist()
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

        $payload = new Payload($collection, $this->validator, true);

        $refreshTTL = 20161;

        $this->storage->shouldReceive('get')
            ->with('foo')
            ->once()
            ->andReturn([]);

        $this->storage->shouldReceive('add')
            ->with('foo', ['valid_until' => $this->testNowTimestamp], $refreshTTL + 1)
            ->once();

        $this->assertTrue($this->blacklist->setRefreshTTL($refreshTTL)->add($payload));
    }

    public function testItShouldReturnTrueEarlyWhenAddingAnItemAndItAlreadyExists()
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

        $payload = new Payload($collection, $this->validator, true);

        $refreshTTL = 20161;

        $this->storage->shouldReceive('get')
            ->with('foo')
            ->once()
            ->andReturn(['valid_until' => $this->testNowTimestamp]);

        $this->storage->shouldReceive('add')
            ->with('foo', ['valid_until' => $this->testNowTimestamp], $refreshTTL + 1)
            ->never();

        $this->assertTrue($this->blacklist->setRefreshTTL($refreshTTL)->add($payload));
    }

    public function testItShouldCheckWhetherATokenHasBeenBlacklisted()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foobar'),
        ];

        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = new Payload($collection, $this->validator);

        $this->storage->shouldReceive('get')->with('foobar')->once()->andReturn(['valid_until' => $this->testNowTimestamp]);

        $this->assertTrue($this->blacklist->has($payload));
    }

    public static function blacklist_provider()
    {
        return [
            [null],
            [0],
            [''],
            [[]],
            [['valid_until' => strtotime('+1day')]],
        ];
    }

    #[DataProvider('blacklist_provider')]
    public function testItShouldCheckWhetherATokenHasNotBeenBlacklisted($result)
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foobar'),
        ];

        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = new Payload($collection, $this->validator);

        $this->storage->shouldReceive('get')->with('foobar')->once()->andReturn($result);
        $this->assertFalse($this->blacklist->has($payload));
    }

    public function testItShouldCheckWhetherATokenHasBeenBlacklistedForever()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foobar'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = new Payload($collection, $this->validator);

        $this->storage->shouldReceive('get')->with('foobar')->once()->andReturn('forever');

        $this->assertTrue($this->blacklist->has($payload));
    }

    public function testItShouldCheckWhetherATokenHasBeenBlacklistedWhenTheTokenIsNotBlacklisted()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foobar'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = new Payload($collection, $this->validator);

        $this->storage->shouldReceive('get')->with('foobar')->once()->andReturn(null);

        $this->assertFalse($this->blacklist->has($payload));
    }

    public function testItShouldRemoveATokenFromTheBlacklist()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foobar'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = new Payload($collection, $this->validator);

        $this->storage->shouldReceive('destroy')->with('foobar')->andReturn(true);
        $this->assertTrue($this->blacklist->remove($payload));
    }

    public function testItShouldSetACustomUniqueKeyForTheBlacklist()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foobar'),
        ];
        $collection = Collection::make($claims);

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        $payload = new Payload($collection, $this->validator);

        $this->storage->shouldReceive('get')->with(1)->once()->andReturn(['valid_until' => $this->testNowTimestamp]);

        $this->assertTrue($this->blacklist->setKey('sub')->has($payload));
        $this->assertSame(1, $this->blacklist->getKey($payload));
    }

    public function testItShouldEmptyTheBlacklist()
    {
        $this->storage->shouldReceive('flush');
        $this->assertTrue($this->blacklist->clear());
    }

    public function testItShouldSetAndGetTheBlacklistGracePeriod()
    {
        $this->assertInstanceOf(Blacklist::class, $this->blacklist->setGracePeriod(15));
        $this->assertSame(15, $this->blacklist->getGracePeriod());
    }

    public function testItShouldSetAndGetTheBlacklistRefreshTtl()
    {
        $this->assertInstanceOf(Blacklist::class, $this->blacklist->setRefreshTTL(15));
        $this->assertSame(15, $this->blacklist->getRefreshTTL());
    }
}
