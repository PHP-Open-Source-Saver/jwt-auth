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
use PHPOpenSourceSaver\JWTAuth\Claims\Collection;
use PHPOpenSourceSaver\JWTAuth\Claims\Custom;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Claims\Factory as ClaimFactory;
use PHPOpenSourceSaver\JWTAuth\Claims\IssuedAt;
use PHPOpenSourceSaver\JWTAuth\Claims\Issuer;
use PHPOpenSourceSaver\JWTAuth\Claims\JwtId;
use PHPOpenSourceSaver\JWTAuth\Claims\NotBefore;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Factory;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;

class FactoryTest extends AbstractTestCase
{
    protected LegacyMockInterface $claimFactory;

    protected LegacyMockInterface $validator;

    protected Factory $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->claimFactory = \Mockery::mock(ClaimFactory::class);
        $this->validator = \Mockery::mock(PayloadValidator::class);
        $this->factory = new Factory($this->claimFactory, $this->validator);
    }

    public function testItShouldReturnAPayloadWhenPassingAnArrayOfClaims()
    {
        $expTime = $this->testNowTimestamp + 3600;

        // these are added from default claims
        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('exp')->andReturn(new Expiration($expTime));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt(123));

        // custom claims that override
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->twice()->with('jti', 'foo')->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('get')->twice()->with('nbf', 123)->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('get')->twice()->with('iat', 123)->andReturn(new IssuedAt(123));

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);

        // once
        $claims = $this->factory->customClaims([
            'sub' => 1,
            'jti' => 'foo',
            'iat' => 123,
            'nbf' => 123,
        ])->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->claims(['sub' => 1, 'jti' => 'foo', 'iat' => 123, 'nbf' => 123])->make();

        $this->assertSame($payload->get('sub'), 1);
        $this->assertSame($payload->get('iat'), 123);
        $this->assertSame($payload['exp'], $expTime);
        $this->assertSame($payload['jti'], 'foo');

        $this->assertInstanceOf(Payload::class, $payload);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldReturnAPayloadWhenChainingClaimMethods()
    {
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->twice()->with('foo', 'baz')->andReturn(new Custom('foo', 'baz'));

        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('exp')->andReturn(new Expiration($this->testNowTimestamp + 3600));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt($this->testNowTimestamp));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore($this->testNowTimestamp));

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);

        // once
        $claims = $this->factory->sub(1)->foo('baz')->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->sub(1)->foo('baz')->make();

        $this->assertSame($payload['sub'], 1);
        $this->assertSame($payload->get('jti'), 'foo');
        $this->assertSame($payload->get('foo'), 'baz');

        $this->assertInstanceOf(Payload::class, $payload);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldReturnAPayloadWhenPassingMiltidimensionalArrayAsCustomClaimToMakeMethod()
    {
        // these are added from default claims
        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('exp')->andReturn(new Expiration($this->testNowTimestamp + 3600));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt(123));

        // custom claims that override
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));
        $this->claimFactory->shouldReceive('get')->twice()->with('foo', ['bar' => [0, 0, 0]])->andReturn(new Custom('foo', ['bar' => [0, 0, 0]]));

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);

        // once
        $claims = $this->factory->sub(1)->foo(['bar' => [0, 0, 0]])->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->sub(1)->foo(['bar' => [0, 0, 0]])->make();

        $this->assertSame($payload->get('sub'), 1);
        $this->assertSame($payload->get('jti'), 'foo');
        $this->assertSame($payload->get('foo'), ['bar' => [0, 0, 0]]);
        $this->assertSame($payload->get('foo.bar'), [0, 0, 0]);

        $this->assertInstanceOf(Payload::class, $payload);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldExcludeTheExpClaimWhenSettingTtlToNull()
    {
        // these are added from default claims
        $this->claimFactory->shouldReceive('make')->twice()->with('iss')->andReturn(new Issuer('/foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('jti')->andReturn(new JwtId('foo'));
        $this->claimFactory->shouldReceive('make')->twice()->with('nbf')->andReturn(new NotBefore(123));
        $this->claimFactory->shouldReceive('make')->twice()->with('iat')->andReturn(new IssuedAt(123));

        // custom claims that override
        $this->claimFactory->shouldReceive('get')->twice()->with('sub', 1)->andReturn(new Subject(1));

        $this->claimFactory->shouldReceive('setTTL')->with(null)->andReturn($this->claimFactory);
        $this->claimFactory->shouldReceive('getTTL')->andReturn(null);

        // once
        $claims = $this->factory->setTTL(null)->sub(1)->buildClaimsCollection();

        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($claims);

        // twice
        $payload = $this->factory->setTTL(null)->sub(1)->make();

        $this->assertNull($payload->get('exp'));

        $this->assertInstanceOf(Payload::class, $payload);
    }

    public function testItShouldExcludeClaimsFromPreviousPayloads()
    {
        $validator = new PayloadValidator();
        $factory = new Factory($this->claimFactory, $validator);

        $fooClaim = new Custom('foo', 'bar');
        $barClaim = new Custom('baz', 'qux');

        $this->claimFactory->shouldReceive('getTTL')->andReturn(60);
        $this->claimFactory->shouldReceive('get')->with('foo', 'bar')->twice()->andReturn($fooClaim);
        $this->claimFactory->shouldReceive('get')->with('baz', 'qux')->once()->andReturn($barClaim);

        $validator->setRequiredClaims([]);
        $payload = $factory->setDefaultClaims([])
            ->customClaims([
                'foo' => 'bar',
                'baz' => 'qux',
            ])->make();

        $this->assertSame($payload->get('foo'), 'bar');
        $this->assertSame($payload->get('baz'), 'qux');

        $payload = $factory->setDefaultClaims([])->customClaims(['foo' => 'bar'])->make(true);

        $this->assertSame($payload->get('foo'), 'bar');
        $this->assertFalse($payload->hasKey('baz'));
    }

    public function testItShouldSetTheDefaultClaims()
    {
        $this->factory->setDefaultClaims(['sub', 'iat']);

        $this->assertSame($this->factory->getDefaultClaims(), ['sub', 'iat']);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldGetPayloadWithAPredefinedCollectionOfClaims()
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

        $payload = $this->factory->withClaims($collection);

        $this->assertInstanceOf(Payload::class, $payload);
        $this->assertSame($payload->get('sub'), 1);
    }

    public function testItShouldGetTheValidator()
    {
        $this->assertInstanceOf(PayloadValidator::class, $this->factory->validator());
    }
}
