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

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RS256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validator;
use Mockery\MockInterface;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;

class LcobucciTest extends AbstractTestCase
{
    /**
     * Mocks {@see Configuration}.
     *
     * @var MockInterface
     */
    protected $config;

    /**
     * @var MockInterface
     */
    protected $parser;

    /**
     * @var MockInterface
     */
    protected $builder;

    /**
     * @var MockInterface
     */
    protected $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->builder = \Mockery::mock(Builder::class);
        $this->parser = \Mockery::mock(Parser::class);
    }

    public function testItShouldReturnTheTokenWhenPassingAValidPayloadToEncode()
    {
        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $dataSet = new DataSet($payload, 'payload');

        $this->builder->shouldReceive('relatedTo')->once()->andReturnSelf(); // sub
        $this->builder->shouldReceive('expiresAt')->once()->andReturnSelf(); // exp
        $this->builder->shouldReceive('issuedAt')->once()->andReturnSelf();  // iat
        $this->builder->shouldReceive('issuedBy')->once()->andReturnSelf();  // iss
        $this->builder
            ->shouldReceive('getToken')
            ->once()
            ->with(\Mockery::type(Signer::class), \Mockery::type(Key::class))
            ->andReturn(new Token\Plain(new DataSet([], 'header'), $dataSet, new Token\Signature('', 'signature')));

        /** @var Token $token */
        $token = $this->getProvider('secret', 'HS256')->encode($payload);

        $this->assertSame('header.payload.signature', $token);
    }

    public function testItShouldThrowAnInvalidExceptionWhenThePayloadCouldNotBeEncoded()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('Could not create token:');

        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $this->builder->shouldReceive('relatedTo')->once()->andReturnSelf(); // sub
        $this->builder->shouldReceive('expiresAt')->once()->andReturnSelf(); // exp
        $this->builder->shouldReceive('issuedAt')->once()->andReturnSelf();  // iat
        $this->builder->shouldReceive('issuedBy')->once()->andReturnSelf();  // iss
        $this->builder
            ->shouldReceive('getToken')
            ->once()
            ->with(\Mockery::type(Signer::class), \Mockery::type(Key::class))
            ->andThrow(new \Exception());

        $this->getProvider('secret', 'HS256')->encode($payload);
    }

    public function testItShouldReturnThePayloadWhenPassingAValidTokenToDecode()
    {
        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $token = \Mockery::mock(Token::class);
        $dataSet = \Mockery::mock(new DataSet($payload, 'payload'));

        $provider = $this->getProvider('secret', 'HS256');

        $this->parser->shouldReceive('parse')->once()->with('foo.bar.baz')->andReturn($token);
        $this->validator->shouldReceive('validate')->once()->with($token, \Mockery::any())->andReturnTrue();
        $token->shouldReceive('claims')->once()->andReturn($dataSet);
        $dataSet->shouldReceive('all')->once()->andReturn($payload);

        $this->assertSame($payload, $provider->decode('foo.bar.baz'));
    }

    public function testItShouldThrowATokenInvalidExceptionWhenTheTokenCouldNotBeDecodedDueToABadSignature()
    {
        $token = \Mockery::mock(Token::class);
        $dataSet = \Mockery::mock(new DataSet(['pay', 'load'], 'payload'));

        $provider = $this->getProvider('secret', 'HS256');

        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Token Signature could not be verified.');

        $this->parser->shouldReceive('parse')->once()->with('foo.bar.baz')->andReturn($token);
        $this->validator->shouldReceive('validate')->once()->with($token, \Mockery::any())->andReturnFalse();
        $token->shouldReceive('claims')->never();
        $dataSet->shouldReceive('all')->never();

        $provider->decode('foo.bar.baz');
    }

    public function testItShouldThrowATokenInvalidExceptionWhenTheTokenCouldNotBeDecoded()
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Could not decode token:');

        $this->parser->shouldReceive('parse')->once()->with('foo.bar.baz')->andThrow(new \InvalidArgumentException());
        $this->parser->shouldReceive('verify')->never();
        $this->parser->shouldReceive('getClaims')->never();

        $this->getProvider('secret', 'HS256')->decode('foo.bar.baz');
    }

    public function testItShouldGenerateATokenWhenUsingAnRsaAlgorithm()
    {
        $dummyPrivateKey = $this->getDummyPrivateKey();
        $dummyPublicKey = $this->getDummyPublicKey();

        $provider = $this->getProvider(
            'does_not_matter',
            'RS256',
            ['private' => $dummyPrivateKey, 'public' => $dummyPublicKey]
        );

        $payload = ['sub' => 1, 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $dataSet = new DataSet($payload, 'payload');

        $this->builder->shouldReceive('relatedTo')->once()->andReturnSelf(); // sub
        $this->builder->shouldReceive('expiresAt')->once()->andReturnSelf(); // exp
        $this->builder->shouldReceive('issuedAt')->once()->andReturnSelf();  // iat
        $this->builder->shouldReceive('issuedBy')->once()->andReturnSelf();  // iss
        $this->builder
            ->shouldReceive('getToken')
            ->once()
            ->with(\Mockery::type(RS256::class), \Mockery::type(Key::class))
            ->andReturn(new Token\Plain(new DataSet([], 'header'), $dataSet, new Token\Signature('', 'signature')));

        $token = $provider->encode($payload);

        $this->assertSame('header.payload.signature', $token);
    }

    public function testItShouldThrowAExceptionWhenTheAlgorithmPassedIsInvalid()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('The given algorithm could not be found');

        $this->parser->shouldReceive('parse')->never();
        $this->parser->shouldReceive('verify')->never();

        $this->getProvider('secret', 'AlgorithmWrong')->decode('foo.bar.baz');
    }

    public function testItShouldThrowAExceptionWhenTheSecretHasBeenUpdatedAndAnOldTokenIsUsed()
    {
        $orignal_secret = 'OF8SQY475aF8uiRuWunK9ZO6VdZDBemk';
        $new_secret = 'vsd1z800ApIihL6HVNyhbGLRyBLD74sZ';

        $payload = ['sub' => '1', 'exp' => $this->testNowTimestamp + 3600, 'iat' => $this->testNowTimestamp, 'iss' => '/foo'];

        $provider = new Lcobucci($orignal_secret, 'HS256', []);
        $token = $provider->encode($payload);

        $this->assertSame($payload, $provider->decode($token));

        $provider->setSecret($new_secret);

        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Token Signature could not be verified.');

        $provider->decode($token);
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

    public function testItShouldCorrectlyInstantiateAnEcdsaSigner()
    {
        $provider = new Lcobucci(
            'does_not_matter',
            'ES256',
            ['private' => 'dummy', 'public' => 'dummy']
        );

        $this->assertSame('ES256', $provider->getConfig()->signer()->algorithmId());
    }

    public function testEncodeAudienceClaimString(): void
    {
        $payload = [
            'aud' => 'foo',
        ];

        $dataSet = new DataSet($payload, 'payload');

        $this->builder->shouldReceive('permittedFor')->once()->andReturnSelf();  // aud
        $this->builder
            ->shouldReceive('getToken')
            ->once()
            ->with(\Mockery::type(Signer::class), \Mockery::type(Key::class))
            ->andReturn(new Token\Plain(new DataSet([], 'header'), $dataSet, new Token\Signature('', 'signature')));

        /** @var Token $token */
        $token = $this->getProvider('secret', 'HS256')->encode($payload);

        $this->assertSame('header.payload.signature', $token);
    }

    public function testEncodeAudienceClaimArray(): void
    {
        $payload = [
            'aud' => ['foo', 'bar'],
        ];

        $dataSet = new DataSet($payload, 'payload');

        $this->builder->shouldReceive('permittedFor')->once()->andReturnSelf();  // aud
        $this->builder
            ->shouldReceive('getToken')
            ->once()
            ->with(\Mockery::type(Signer::class), \Mockery::type(Key::class))
            ->andReturn(new Token\Plain(new DataSet([], 'header'), $dataSet, new Token\Signature('', 'signature')));

        /** @var Token $token */
        $token = $this->getProvider('secret', 'HS256')->encode($payload);

        $this->assertSame('header.payload.signature', $token);
    }

    public function getProvider($secret, $algo, array $keys = [])
    {
        $provider = new Lcobucci($secret, $algo, $keys);

        $this->validator = \Mockery::mock(Validator::class);
        $this->config = \Mockery::mock($provider->getConfig());

        $provider = new Lcobucci($secret, $algo, $keys, $this->config);

        $this->config->shouldReceive('builder')->andReturn($this->builder);
        $this->config->shouldReceive('parser')->andReturn($this->parser);
        $this->config->shouldReceive('validator')->andReturn($this->validator);

        $constraint = \Mockery::mock(Constraint::class);
        $constraint->shouldReceive('assert')->andReturn();
        $this->config->shouldReceive('validationConstraints')->andReturn([$constraint]);

        return $provider;
    }

    public function getDummyPrivateKey()
    {
        return file_get_contents(__DIR__.'/../Keys/id_rsa');
    }

    public function getDummyPublicKey()
    {
        return file_get_contents(__DIR__.'/../Keys/id_rsa.pub');
    }
}
