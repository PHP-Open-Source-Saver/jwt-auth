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

use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Claims\Custom;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Claims\Factory;
use PHPOpenSourceSaver\JWTAuth\Claims\IssuedAt;
use PHPOpenSourceSaver\JWTAuth\Claims\Issuer;
use PHPOpenSourceSaver\JWTAuth\Claims\JwtId;
use PHPOpenSourceSaver\JWTAuth\Claims\NotBefore;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;
use PHPOpenSourceSaver\JWTAuth\Test\Fixtures\Foo;

class FactoryTest extends AbstractTestCase
{
    protected Factory $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new Factory(Request::create('/foo', 'GET'));
    }

    public function testItShouldSetTheRequest()
    {
        $factory = $this->factory->setRequest(Request::create('/bar', 'GET'));
        $this->assertInstanceOf(Factory::class, $factory);
    }

    public function testItShouldSetTheTtl()
    {
        $this->assertInstanceOf(Factory::class, $this->factory->setTTL(30));
    }

    public function testItShouldGetTheTtl()
    {
        $this->factory->setTTL($ttl = 30);
        $this->assertSame($ttl, $this->factory->getTTL());
    }

    public function testItShouldGetADefinedClaimInstanceWhenPassingANameAndValue()
    {
        $this->assertInstanceOf(Subject::class, $this->factory->get('sub', 1));
        $this->assertInstanceOf(Issuer::class, $this->factory->get('iss', 'http://example.com'));
        $this->assertInstanceOf(Expiration::class, $this->factory->get('exp', $this->testNowTimestamp + 3600));
        $this->assertInstanceOf(NotBefore::class, $this->factory->get('nbf', $this->testNowTimestamp));
        $this->assertInstanceOf(IssuedAt::class, $this->factory->get('iat', $this->testNowTimestamp));
        $this->assertInstanceOf(JwtId::class, $this->factory->get('jti', 'foo'));
    }

    public function testItShouldGetACustomClaimInstanceWhenPassingANonDefinedNameAndValue()
    {
        $this->assertInstanceOf(Custom::class, $this->factory->get('foo', ['bar']));
    }

    public function testItShouldMakeAClaimInstanceWithAValue()
    {
        $iat = $this->factory->make('iat');
        $this->assertSame($iat->getValue(), $this->testNowTimestamp);
        $this->assertInstanceOf(IssuedAt::class, $iat);

        $nbf = $this->factory->make('nbf');
        $this->assertSame($nbf->getValue(), $this->testNowTimestamp);
        $this->assertInstanceOf(NotBefore::class, $nbf);

        $iss = $this->factory->make('iss');
        $this->assertSame($iss->getValue(), 'http://localhost/foo');
        $this->assertInstanceOf(Issuer::class, $iss);

        $exp = $this->factory->make('exp');
        $this->assertSame($exp->getValue(), $this->testNowTimestamp + 3600);
        $this->assertInstanceOf(Expiration::class, $exp);

        $jti = $this->factory->make('jti');
        $this->assertInstanceOf(JwtId::class, $jti);
    }

    public function testItShouldExtendClaimFactoryToAddACustomClaim()
    {
        $this->factory->extend('foo', Foo::class);

        $this->assertInstanceOf(Foo::class, $this->factory->get('foo', 'bar'));
    }
}
