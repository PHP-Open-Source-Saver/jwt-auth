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

namespace PHPOpenSourceSaver\JWTAuth\Test\Providers\JWT;

use Mockery\MockInterface;
use Namshi\JOSE\JWS;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Namshi;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;

class NamshiTest extends AbstractTestCase
{
    /**
     * @var MockInterface
     */
    protected $jws;

    /**
     * @var Namshi
     */
    protected $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->jws = \Mockery::mock(JWS::class);
    }

    public function testItShouldReturnTheTokenWhenPassingAValidPayloadToEncode()
    {
        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $this->jws->shouldReceive('setPayload')->once()->with($payload)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('sign')->once()->with('secret', null)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('getTokenString')->once()->andReturn('foo.bar.baz');

        $token = $this->getProvider('secret', 'HS256')->encode($payload);

        $this->assertSame('foo.bar.baz', $token);
    }

    public function getProvider($secret, $algo, array $keys = [])
    {
        return new Namshi($this->jws, $secret, $algo, $keys);
    }

    public function testItShouldThrowAnInvalidExceptionWhenThePayloadCouldNotBeEncoded()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('Could not create token:');

        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $this->jws->shouldReceive('setPayload')->once()->with($payload)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('sign')->andThrow(new \Exception());

        $this->getProvider('secret', 'HS256')->encode($payload);
    }

    public function testItShouldReturnThePayloadWhenPassingAValidTokenToDecode()
    {
        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $this->jws->shouldReceive('load')->once()->with('foo.bar.baz', false)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('verify')->once()->with('secret', 'HS256')->andReturn(true);
        $this->jws->shouldReceive('getPayload')->andReturn($payload);

        $this->assertSame($payload, $this->getProvider('secret', 'HS256')->decode('foo.bar.baz'));
    }

    public function testItShouldThrowATokenInvalidExceptionWhenTheTokenCouldNotBeDecodedDueToABadSignature()
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Token Signature could not be verified.');

        $this->jws->shouldReceive('load')->once()->with('foo.bar.baz', false)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('verify')->once()->with('secret', 'HS256')->andReturn(false);
        $this->jws->shouldReceive('getPayload')->never();

        $this->getProvider('secret', 'HS256')->decode('foo.bar.baz');
    }

    public function testItShouldThrowATokenInvalidExceptionWhenTheTokenCouldNotBeDecoded()
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Could not decode token:');

        $this->jws->shouldReceive('load')->once()->with('foo.bar.baz', false)->andThrow(new \InvalidArgumentException());
        $this->jws->shouldReceive('verify')->never();
        $this->jws->shouldReceive('getPayload')->never();

        $this->getProvider('secret', 'HS256')->decode('foo.bar.baz');
    }

    public function testItShouldGenerateATokenWhenUsingAnRsaAlgorithm()
    {
        $provider = $this->getProvider(
            'does_not_matter',
            'RS256',
            ['private' => $this->getDummyPrivateKey(), 'public' => $this->getDummyPublicKey()]
        );

        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $this->jws->shouldReceive('setPayload')->once()->with($payload)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('sign')->once()->with($this->getDummyPrivateKey(), null)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('getTokenString')->once()->andReturn('foo.bar.baz');

        $token = $provider->encode($payload);

        $this->assertSame('foo.bar.baz', $token);
    }

    public function getDummyPrivateKey()
    {
        return file_get_contents(__DIR__.'/../Keys/id_rsa');
    }

    public function getDummyPublicKey()
    {
        return file_get_contents(__DIR__.'/../Keys/id_rsa.pub');
    }

    public function testItShouldGenerateATokenWhenUsingAnEcdsaAlgorithm()
    {
        $provider = $this->getProvider(
            'does_not_matter',
            'ES256',
            ['private' => $this->getDummyPrivateKey(), 'public' => $this->getDummyPublicKey()]
        );

        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $this->jws->shouldReceive('setPayload')->once()->with($payload)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('sign')->once()->with($this->getDummyPrivateKey(), null)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('getTokenString')->once()->andReturn('foo.bar.baz');

        $token = $provider->encode($payload);

        $this->assertSame('foo.bar.baz', $token);
    }

    public function testItShouldDecodeATokenWhenUsingAnRsaAlgorithm()
    {
        $provider = $this->getProvider(
            'does_not_matter',
            'RS256',
            ['private' => $this->getDummyPrivateKey(), 'public' => $this->getDummyPublicKey()]
        );

        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $this->jws->shouldReceive('setPayload')->once()->with($payload)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('sign')->once()->with($this->getDummyPrivateKey(), null)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('getTokenString')->once()->andReturn('foo.bar.baz');

        $token = $provider->encode($payload);

        $this->assertSame('foo.bar.baz', $token);
    }

    public function testItShouldThrowAExceptionWhenTheAlgorithmPassedIsInvalid()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('The given algorithm could not be found');

        $this->jws->shouldReceive('load')->once()->with('foo.bar.baz', false)->andReturn(\Mockery::self());
        $this->jws->shouldReceive('verify')->with('secret', 'AlgorithmWrong')->andReturn(true);

        $this->getProvider('secret', 'AlgorithmWrong')->decode('foo.bar.baz');
    }

    public function testItShouldReturnThePublicKey()
    {
        $provider = $this->getProvider(
            'does_not_matter',
            'RS256',
            $keys = ['private' => $this->getDummyPrivateKey(), 'public' => $this->getDummyPublicKey()]
        );

        $this->assertSame($keys['public'], $provider->getPublicKey());
    }

    public function testItShouldReturnTheKeys()
    {
        $provider = $this->getProvider(
            'does_not_matter',
            'RS256',
            $keys = ['private' => $this->getDummyPrivateKey(), 'public' => $this->getDummyPublicKey()]
        );

        $this->assertSame($keys, $provider->getKeys());
    }
}
