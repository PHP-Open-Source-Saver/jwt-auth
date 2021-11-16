<?php

/*
 * This file is part of jwt-auth.
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

        $this->configuration = include __DIR__ . '/../config/config.php';
    }

    /** @test */
    public function secret_should_be_null()
    {
        $this->assertNull($this->configuration['secret']);
    }

    /** @test */
    public function keys_should_be_null()
    {
        $this->assertNull($this->configuration['keys']['public']);
        $this->assertNull($this->configuration['keys']['private']);
        $this->assertNull($this->configuration['keys']['passphrase']);
    }

    /** @test */
    public function ttl_should_be_set()
    {
        $this->assertEquals(60, $this->configuration['ttl']);
    }

    /** @test */
    public function refresh_ttl_should_be_set()
    {
        $this->assertEquals(20160, $this->configuration['refresh_ttl']);
    }

    /** @test */
    public function algo_should_be_hs256()
    {
        $this->assertEquals('HS256', $this->configuration['algo']);
    }

    /** @test */
    public function required_claims_should_be_set()
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
    public function persisted_claims_should_be_empty()
    {
        $this->assertIsArray($this->configuration['persistent_claims']);
        $this->assertCount(0, $this->configuration['persistent_claims']);
    }

    /** @test */
    public function subject_should_be_locked()
    {
        $this->assertTrue($this->configuration['lock_subject']);
    }

    /** @test */
    public function leeway_should_be_set()
    {
        $this->assertEquals(0, $this->configuration['leeway']);
    }

    /** @test */
    public function blacklist_should_be_enabled()
    {
        $this->assertTrue($this->configuration['blacklist_enabled']);
    }

    /** @test */
    public function blacklist_grace_period_should_be_set()
    {
        $this->assertEquals(0, $this->configuration['blacklist_grace_period']);
    }

    /** @test */
    public function show_black_list_exception_should_be_disabled()
    {
        $this->assertEquals(0, $this->configuration['show_black_list_exception']);
    }

    /** @test */
    public function decrypt_cookies_should_be_disabled()
    {
        $this->assertFalse($this->configuration['decrypt_cookies']);
    }

    /** @test */
    public function providers_should_be_set()
    {
        $this->assertEquals(Lcobucci::class, $this->configuration['providers']['jwt']);
        $this->assertEquals(AuthIlluminate::class, $this->configuration['providers']['auth']);
        $this->assertEquals(StorageIlluminate::class, $this->configuration['providers']['storage']);
    }
}
