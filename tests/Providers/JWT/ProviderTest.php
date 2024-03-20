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

class ProviderTest extends AbstractTestCase
{
    /**
     * @var JWTProviderStub
     */
    protected $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->provider = new JWTProviderStub('secret', 'HS256', []);
    }

    public function testItShouldSetTheAlgo()
    {
        $this->provider->setAlgo('HS512');

        $this->assertSame('HS512', $this->provider->getAlgo());
    }

    public function testItShouldSetTheSecret()
    {
        $this->provider->setSecret('foo');

        $this->assertSame('foo', $this->provider->getSecret());
    }
}
