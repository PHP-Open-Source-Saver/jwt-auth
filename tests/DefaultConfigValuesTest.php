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

    /** @test */
    public function secretShouldBeNull()
    {
        $this->assertNull($this->configuration['secret']);
    }

    /** @test */
    public function keysShouldBeNull()
    {
        $this->assertNull($this->configuration['keys']['public']);
        $this->assertNull($this->configuration['keys']['private']);
        $this->assertNull($this->configuration['keys']['passphrase']);
    }

    /** @test */
    public function ttlShouldBeSet()
    {
        $this->assertEquals(60, $this->configuration['ttl']);
    }

    /** @test */
    public function refreshTtlShouldBeSet()
    {
        $this->assertEquals(20160, $this->configuration['refresh_ttl']);
    }

    /** @test */
    public function algoShouldBeHs256()
    {
        $this->assertEquals('HS256', $this->configuration['algo']);
    }

    /** @test */
    public function requiredClaimsShouldBeSet()
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

    /** @test */
    public function persistedClaimsShouldBeEmpty()
    {
        $this->assertIsArray($this->configuration['persistent_claims']);
        $this->assertCount(0, $this->configuration['persistent_claims']);
    }

    /** @test */
    public function subjectShouldBeLocked()
    {
        $this->assertTrue($this->configuration['lock_subject']);
    }

    /** @test */
    public function leewayShouldBeSet()
    {
        $this->assertEquals(0, $this->configuration['leeway']);
    }

    /** @test */
    public function blacklistShouldBeEnabled()
    {
        $this->assertTrue($this->configuration['blacklist_enabled']);
    }

    /** @test */
    public function blacklistGracePeriodShouldBeSet()
    {
        $this->assertEquals(0, $this->configuration['blacklist_grace_period']);
    }

    /** @test */
    public function showBlackListExceptionShouldBeDisabled()
    {
        $this->assertEquals(0, $this->configuration['show_black_list_exception']);
    }

    /** @test */
    public function decryptCookiesShouldBeDisabled()
    {
        $this->assertFalse($this->configuration['decrypt_cookies']);
    }

    /** @test */
    public function providersShouldBeSet()
    {
        $this->assertEquals(Lcobucci::class, $this->configuration['providers']['jwt']);
        $this->assertEquals(AuthIlluminate::class, $this->configuration['providers']['auth']);
        $this->assertEquals(StorageIlluminate::class, $this->configuration['providers']['storage']);
    }
}
