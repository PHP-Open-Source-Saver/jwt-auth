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

namespace PHPOpenSourceSaver\JWTAuth\Test\Providers\Auth;

use Illuminate\Contracts\Auth\Guard;
use Mockery\MockInterface;
use PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate as Auth;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;

class IlluminateTest extends AbstractTestCase
{
    /**
     * @var MockInterface|Guard
     */
    protected $authManager;

    /**
     * @var Auth
     */
    protected $auth;

    public function setUp(): void
    {
        parent::setUp();

        $this->authManager = \Mockery::mock(Guard::class);
        $this->auth = new Auth($this->authManager);
    }

    public function testItShouldReturnTrueIfCredentialsAreValid()
    {
        $this->authManager->shouldReceive('once')->once()->with(['email' => 'foo@bar.com', 'password' => 'foobar'])->andReturn(true);
        $this->assertTrue($this->auth->byCredentials(['email' => 'foo@bar.com', 'password' => 'foobar']));
    }

    public function testItShouldReturnTrueIfUserIsFound()
    {
        $this->authManager->shouldReceive('onceUsingId')->once()->with(123)->andReturn(true);
        $this->assertTrue($this->auth->byId(123));
    }

    public function testItShouldReturnFalseIfUserIsNotFound()
    {
        $this->authManager->shouldReceive('onceUsingId')->once()->with(123)->andReturn(false);
        $this->assertFalse($this->auth->byId(123));
    }

    public function testItShouldReturnTheCurrentlyAuthenticatedUser()
    {
        $this->authManager->shouldReceive('user')->once()->andReturn((object) ['id' => 1]);
        $this->assertSame($this->auth->user()->id, 1);
    }
}
