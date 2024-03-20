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

use PHPOpenSourceSaver\JWTAuth\Exceptions\SecretMissingException;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;
use PHPOpenSourceSaver\JWTAuth\Test\Stubs\JWTProviderStub;

class ProviderEmptySecretTest extends AbstractTestCase
{
    /**
     * @var JWTProviderStub
     */
    protected $provider;

    public function testAsymmetricNoSecret()
    {
        $this->provider = new JWTProviderStub(null, 'RS256', ['public' => '123', 'private' => '456']);

        $this->assertSame(null, $this->provider->getSecret());
    }

    public function testAsymmetricPublicMissing()
    {
        $this->expectException(SecretMissingException::class);
        $this->provider = new JWTProviderStub(null, 'RS256', ['public' => null, 'private' => '456']);

        $this->assertSame(null, $this->provider->getSecret());
    }

    public function testAsymmetricPrivateMissing()
    {
        $this->expectException(SecretMissingException::class);
        $this->provider = new JWTProviderStub(null, 'RS256', ['public' => '123', 'private' => null]);

        $this->assertSame(null, $this->provider->getSecret());
    }

    public function testSymmetricKeyMissing()
    {
        $this->expectException(SecretMissingException::class);
        $this->provider = new JWTProviderStub(null, 'RS256', ['public' => null, 'private' => null]);

        $this->assertSame(null, $this->provider->getSecret());
    }
}
