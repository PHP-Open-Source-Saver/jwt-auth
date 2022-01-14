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

use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;
use PHPOpenSourceSaver\JWTAuth\Test\Stubs\JWTProviderStub;

class ProviderEmptySecretTest extends AbstractTestCase
{
    /**
     * @var JWTProviderStub
     */
    protected $provider;

    /** @test */
    public function noExceptionForNULL()
    {
        $this->provider = new JWTProviderStub(null, 'RS256', []);

        $this->provider->setSecret(null);
        $this->assertSame(null, $this->provider->getSecret());
    }
}
