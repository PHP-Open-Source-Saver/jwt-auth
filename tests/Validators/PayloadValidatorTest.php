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

namespace PHPOpenSourceSaver\JWTAuth\Test\Validators;

use PHPOpenSourceSaver\JWTAuth\Claims\Collection;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Claims\IssuedAt;
use PHPOpenSourceSaver\JWTAuth\Claims\Issuer;
use PHPOpenSourceSaver\JWTAuth\Claims\JwtId;
use PHPOpenSourceSaver\JWTAuth\Claims\NotBefore;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;

class PayloadValidatorTest extends AbstractTestCase
{
    protected PayloadValidator $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new PayloadValidator();
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldReturnTrueWhenProvidingAValidPayload()
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

        $this->assertTrue($this->validator->isValid($collection));
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldThrowAnExceptionWhenProvidingAnExpiredPayload()
    {
        $this->expectException(TokenExpiredException::class);
        $this->expectExceptionMessage('Token has expired');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp - 1440),
            new NotBefore($this->testNowTimestamp - 3660),
            new IssuedAt($this->testNowTimestamp - 3660),
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->validator->check($collection);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldThrowAnExceptionWhenProvidingAnInvalidNbfClaim()
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Not Before (nbf) timestamp cannot be in the future');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 1440),
            new NotBefore($this->testNowTimestamp + 3660),
            new IssuedAt($this->testNowTimestamp - 3660),
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->validator->check($collection);
    }

    public function testItShouldThrowAnExceptionWhenProvidingAnInvalidIatClaim()
    {
        $this->expectException(InvalidClaimException::class);
        $this->expectExceptionMessage('Invalid value provided for claim [iat]');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 1440),
            new NotBefore($this->testNowTimestamp - 3660),
            new IssuedAt($this->testNowTimestamp + 3660),
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->validator->check($collection);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldThrowAnExceptionWhenProvidingAnInvalidPayload()
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('JWT payload does not contain the required claims');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
        ];

        $collection = Collection::make($claims);

        $this->validator->check($collection);
    }

    public function testItShouldThrowAnExceptionWhenProvidingAnInvalidExpiry()
    {
        $this->expectException(InvalidClaimException::class);
        $this->expectExceptionMessage('Invalid value provided for claim [exp]');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration('foo'),
            new NotBefore($this->testNowTimestamp - 3660),
            new IssuedAt($this->testNowTimestamp + 3660),
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->validator->check($collection);
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldSetTheRequiredClaims()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
        ];

        $collection = Collection::make($claims);

        $this->assertTrue($this->validator->setRequiredClaims(['iss', 'sub'])->isValid($collection));
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldCheckTheTokenInTheRefreshContext()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp - 1000),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp - 2600), // this is LESS than the refresh ttl at 1 hour
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->assertTrue(
            $this->validator->setRefreshFlow()->setRefreshTTL(60)->isValid($collection)
        );
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldReturnTrueIfTheRefreshTtlIsNull()
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp - 1000),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp - 2600), // this is LESS than the refresh ttl at 1 hour
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->assertTrue(
            $this->validator->setRefreshFlow()->setRefreshTTL(null)->isValid($collection)
        );
    }

    /**
     * @throws InvalidClaimException
     */
    public function testItShouldThrowAnExceptionIfTheTokenCannotBeRefreshed()
    {
        $this->expectException(TokenExpiredException::class);
        $this->expectExceptionMessage('Token has expired and can no longer be refreshed');

        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp - 5000), // this is MORE than the refresh ttl at 1 hour, so is invalid
            new JwtId('foo'),
        ];

        $collection = Collection::make($claims);

        $this->validator->setRefreshFlow()->setRefreshTTL(60)->check($collection);
    }
}
