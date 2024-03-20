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

namespace PHPOpenSourceSaver\JWTAuth\Test\Claims;

use Carbon\Carbon;
use Mockery\LegacyMockInterface;
use PHPOpenSourceSaver\JWTAuth\Claims\Collection;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Claims\IssuedAt;
use PHPOpenSourceSaver\JWTAuth\Claims\Issuer;
use PHPOpenSourceSaver\JWTAuth\Claims\JwtId;
use PHPOpenSourceSaver\JWTAuth\Claims\NotBefore;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;

class DatetimeClaimTest extends AbstractTestCase
{
    protected LegacyMockInterface $validator;

    protected array $claimsTimestamp;

    /**
     * @throws InvalidClaimException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->validator = \Mockery::mock(PayloadValidator::class);
        $this->validator->shouldReceive('setRefreshFlow->check');

        $this->claimsTimestamp = [
            'sub' => new Subject(1),
            'iss' => new Issuer('http://example.com'),
            'exp' => new Expiration($this->testNowTimestamp + 3600),
            'nbf' => new NotBefore($this->testNowTimestamp),
            'iat' => new IssuedAt($this->testNowTimestamp),
            'jti' => new JwtId('foo'),
        ];
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldHandleCarbonClaims()
    {
        $testCarbon = Carbon::createFromTimestampUTC($this->testNowTimestamp);
        $testCarbonCopy = clone $testCarbon;

        $this->assertInstanceOf(Carbon::class, $testCarbon);
        $this->assertInstanceOf(\DateTime::class, $testCarbon);
        $this->assertInstanceOf(\DateTimeInterface::class, $testCarbon);

        $claimsDatetime = [
            'sub' => new Subject(1),
            'iss' => new Issuer('http://example.com'),
            'exp' => new Expiration($testCarbonCopy->addHour()),
            'nbf' => new NotBefore($testCarbon),
            'iat' => new IssuedAt($testCarbon),
            'jti' => new JwtId('foo'),
        ];

        $payloadTimestamp = new Payload(Collection::make($this->claimsTimestamp), $this->validator);
        $payloadDatetime = new Payload(Collection::make($claimsDatetime), $this->validator);

        $this->assertEquals($payloadTimestamp, $payloadDatetime);
    }

    public function testItShouldHandleDatetimeClaims()
    {
        $testDateTime = \DateTime::createFromFormat('U', $this->testNowTimestamp);
        $testDateTimeCopy = clone $testDateTime;

        $this->assertInstanceOf(\DateTime::class, $testDateTime);
        $this->assertInstanceOf(\DateTimeInterface::class, $testDateTime);

        $claimsDatetime = [
            'sub' => new Subject(1),
            'iss' => new Issuer('http://example.com'),
            'exp' => new Expiration($testDateTimeCopy->modify('+3600 seconds')),
            'nbf' => new NotBefore($testDateTime),
            'iat' => new IssuedAt($testDateTime),
            'jti' => new JwtId('foo'),
        ];

        $payloadTimestamp = new Payload(Collection::make($this->claimsTimestamp), $this->validator);
        $payloadDatetime = new Payload(Collection::make($claimsDatetime), $this->validator);

        $this->assertEquals($payloadTimestamp, $payloadDatetime);
    }

    public function testItShouldHandleDatetimeImmutableClaims()
    {
        $testDateTimeImmutable = \DateTimeImmutable::createFromFormat('U', (string) $this->testNowTimestamp);

        $this->assertInstanceOf(\DateTimeImmutable::class, $testDateTimeImmutable);
        $this->assertInstanceOf(\DateTimeInterface::class, $testDateTimeImmutable);

        $claimsDatetime = [
            'sub' => new Subject(1),
            'iss' => new Issuer('http://example.com'),
            'exp' => new Expiration($testDateTimeImmutable->modify('+3600 seconds')),
            'nbf' => new NotBefore($testDateTimeImmutable),
            'iat' => new IssuedAt($testDateTimeImmutable),
            'jti' => new JwtId('foo'),
        ];

        $payloadTimestamp = new Payload(Collection::make($this->claimsTimestamp), $this->validator);
        $payloadDatetime = new Payload(Collection::make($claimsDatetime), $this->validator);

        $this->assertEquals($payloadTimestamp, $payloadDatetime);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldHandleDatetintervalClaims()
    {
        $testDateInterval = new \DateInterval('PT1H');

        $this->assertInstanceOf(\DateInterval::class, $testDateInterval);

        $claimsDateInterval = [
            'sub' => new Subject(1),
            'iss' => new Issuer('http://example.com'),
            'exp' => new Expiration($testDateInterval),
            'nbf' => new NotBefore($this->testNowTimestamp),
            'iat' => new IssuedAt($this->testNowTimestamp),
            'jti' => new JwtId('foo'),
        ];

        $payloadTimestamp = new Payload(Collection::make($this->claimsTimestamp), $this->validator);
        $payloadDateInterval = new Payload(Collection::make($claimsDateInterval), $this->validator);

        $this->assertEquals($payloadTimestamp, $payloadDateInterval);
    }
}
