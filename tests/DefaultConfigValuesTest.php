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

use Illuminate\Support\Env;
use PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate as AuthIlluminate;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci;
use PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate as StorageIlluminate;

class DefaultConfigValuesTest extends AbstractTestCase
{
    protected $configuration = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->configuration = include __DIR__.'/../config/config.php';
    }

    public function testSecretShouldBeNull()
    {
        $this->assertNull($this->configuration['secret']);
    }

    public function testKeysShouldBeNull()
    {
        $this->assertNull($this->configuration['keys']['public']);
        $this->assertNull($this->configuration['keys']['private']);
        $this->assertNull($this->configuration['keys']['passphrase']);
    }

    public function testTtlShouldBeSet()
    {
        $this->assertSame(60, $this->configuration['ttl']);
    }

    public function testTtlShouldAllowNullFromEnv()
    {
        Env::getRepository()->set('JWT_TTL', 'null');

        try {
            $configuration = include __DIR__.'/../config/config.php';

            $this->assertNull($configuration['ttl']);
        } finally {
            Env::getRepository()->clear('JWT_TTL');
        }
    }

    public function testTtlShouldCastNumericEnvStringToInt()
    {
        Env::getRepository()->set('JWT_TTL', '120');

        try {
            $configuration = include __DIR__.'/../config/config.php';

            $this->assertSame(120, $configuration['ttl']);
        } finally {
            Env::getRepository()->clear('JWT_TTL');
        }
    }

    public function testRefreshIatShouldBeSet()
    {
        $this->assertEquals(false, $this->configuration['refresh_iat']);
    }

    public function testRefreshTtlShouldBeSet()
    {
        $this->assertSame(20160, $this->configuration['refresh_ttl']);
    }

    public function testRefreshTtlShouldAllowNullFromEnv()
    {
        Env::getRepository()->set('JWT_REFRESH_TTL', 'null');

        try {
            $configuration = include __DIR__.'/../config/config.php';

            $this->assertNull($configuration['refresh_ttl']);
        } finally {
            Env::getRepository()->clear('JWT_REFRESH_TTL');
        }
    }

    public function testRefreshTtlShouldCastNumericEnvStringToInt()
    {
        Env::getRepository()->set('JWT_REFRESH_TTL', '1440');

        try {
            $configuration = include __DIR__.'/../config/config.php';

            $this->assertSame(1440, $configuration['refresh_ttl']);
        } finally {
            Env::getRepository()->clear('JWT_REFRESH_TTL');
        }
    }

    public function testAlgoShouldBeHs256()
    {
        $this->assertEquals('HS256', $this->configuration['algo']);
    }

    public function testRequiredClaimsShouldBeSet()
    {
        $this->assertIsArray($this->configuration['required_claims']);
        $this->assertCount(6, $this->configuration['required_claims']);

        $this->assertTrue(in_array('iss', $this->configuration['required_claims']));
        $this->assertTrue(in_array('iat', $this->configuration['required_claims']));
        $this->assertTrue(in_array('exp', $this->configuration['required_claims']));
        $this->assertTrue(in_array('nbf', $this->configuration['required_claims']));
        $this->assertTrue(in_array('sub', $this->configuration['required_claims']));
        $this->assertTrue(in_array('jti', $this->configuration['required_claims']));
    }

    public function testPersistedClaimsShouldBeEmpty()
    {
        $this->assertIsArray($this->configuration['persistent_claims']);
        $this->assertCount(0, $this->configuration['persistent_claims']);
    }

    public function testSubjectShouldBeLocked()
    {
        $this->assertTrue($this->configuration['lock_subject']);
    }

    public function testLeewayShouldBeSet()
    {
        $this->assertEquals(0, $this->configuration['leeway']);
    }

    public function testBlacklistShouldBeEnabled()
    {
        $this->assertTrue($this->configuration['blacklist_enabled']);
    }

    public function testBlacklistGracePeriodShouldBeSet()
    {
        $this->assertEquals(0, $this->configuration['blacklist_grace_period']);
    }

    public function testShowBlackListExceptionShouldBeDisabled()
    {
        $this->assertTrue($this->configuration['show_black_list_exception']);
    }

    public function testDecryptCookiesShouldBeDisabled()
    {
        $this->assertFalse($this->configuration['decrypt_cookies']);
    }

    public function testProvidersShouldBeSet()
    {
        $this->assertEquals(Lcobucci::class, $this->configuration['providers']['jwt']);
        $this->assertEquals(AuthIlluminate::class, $this->configuration['providers']['auth']);
        $this->assertEquals(StorageIlluminate::class, $this->configuration['providers']['storage']);
    }

    public function testCookieKeyNameShouldBeSet()
    {
        $this->assertEquals('token', $this->configuration['cookie_key_name']);
    }
}
