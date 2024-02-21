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

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Mockery\LegacyMockInterface;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\UserNotDefinedException;
use PHPOpenSourceSaver\JWTAuth\Factory;
use PHPOpenSourceSaver\JWTAuth\JWT;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Test\Stubs\LaravelUserStub;

class JWTGuardTest extends AbstractTestCase
{
    protected LegacyMockInterface $jwt;

    protected LegacyMockInterface $provider;

    protected JWTGuard $guard;

    protected LegacyMockInterface $eventDispatcher;

    public function setUp(): void
    {
        parent::setUp();

        $this->jwt = \Mockery::mock(JWT::class);
        $this->provider = \Mockery::mock(EloquentUserProvider::class);
        $this->eventDispatcher = \Mockery::mock(Dispatcher::class);
        $this->guard = new JWTGuard(
            $this->jwt,
            $this->provider,
            Request::create('/foo', 'GET'),
            $this->eventDispatcher
        );
    }

    /** @test */
    public function itShouldGetTheRequest()
    {
        $this->assertInstanceOf(Request::class, $this->guard->getRequest());
    }

    /** @test */
    public function itShouldGetTheAuthenticatedUserIfAValidTokenIsProvided()
    {
        $payload = \Mockery::mock(Payload::class);
        $payload->shouldReceive('offsetGet')->once()->with('sub')->andReturn(1);

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn('foo.bar.baz');
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn($payload);
        $this->jwt->shouldReceive('checkSubjectModel')
            ->once()
            ->with('\PHPOpenSourceSaver\JWTAuth\Test\Stubs\LaravelUserStub')
            ->andReturn(true);

        $this->provider->shouldReceive('getModel')
            ->once()
            ->andReturn('\PHPOpenSourceSaver\JWTAuth\Test\Stubs\LaravelUserStub');
        $this->provider->shouldReceive('retrieveById')
            ->once()
            ->with(1)
            ->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->guard->user()->id);

        // check that the user is stored on the object next time round
        $this->assertSame(1, $this->guard->user()->id);
        $this->assertTrue($this->guard->check());

        // also make sure userOrFail does not fail
        $this->assertSame(1, $this->guard->userOrFail()->id);
    }

    /** @test */
    public function itShouldGetTheAuthenticatedUserIfAValidTokenIsProvidedAndNotThrowAnException()
    {
        $payload = \Mockery::mock(Payload::class);
        $payload->shouldReceive('offsetGet')->once()->with('sub')->andReturn(1);

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn('foo.bar.baz');
        $this->jwt->shouldReceive('check')->once()->with(true)->andReturn($payload);
        $this->jwt->shouldReceive('checkSubjectModel')
            ->once()
            ->with('\PHPOpenSourceSaver\JWTAuth\Test\Stubs\LaravelUserStub')
            ->andReturn(true);

        $this->provider->shouldReceive('getModel')
            ->once()
            ->andReturn('\PHPOpenSourceSaver\JWTAuth\Test\Stubs\LaravelUserStub');
        $this->provider->shouldReceive('retrieveById')
            ->once()
            ->with(1)
            ->andReturn((object) ['id' => 1]);

        $this->assertSame(1, $this->guard->userOrFail()->id);

        // check that the user is stored on the object next time round
        $this->assertSame(1, $this->guard->userOrFail()->id);
        $this->assertTrue($this->guard->check());
    }

    /** @test */
    public function itShouldReturnNullIfAnInvalidTokenIsProvided()
    {
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->twice()->andReturn('invalid.token.here');
        $this->jwt->shouldReceive('check')->twice()->andReturn(false);
        $this->jwt->shouldReceive('getPayload->get')->never();
        $this->provider->shouldReceive('retrieveById')->never();

        $this->assertNull($this->guard->user()); // once
        $this->assertFalse($this->guard->check()); // twice
    }

    /** @test */
    public function itShouldReturnNullIfNoTokenIsProvided()
    {
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->andReturn(false);
        $this->jwt->shouldReceive('check')->never();
        $this->jwt->shouldReceive('getPayload->get')->never();
        $this->provider->shouldReceive('retrieveById')->never();

        $this->assertNull($this->guard->user());
        $this->assertFalse($this->guard->check());
    }

    /** @test */
    public function itShouldThrowAnExceptionIfAnInvalidTokenIsProvided()
    {
        $this->expectException(UserNotDefinedException::class);
        $this->expectExceptionMessage('An error occurred');

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->twice()->andReturn('invalid.token.here');
        $this->jwt->shouldReceive('check')->twice()->andReturn(false);
        $this->jwt->shouldReceive('getPayload->get')->never();
        $this->provider->shouldReceive('retrieveById')->never();

        $this->assertFalse($this->guard->check()); // once
        $this->guard->userOrFail(); // twice, throws the exception
    }

    /** @test */
    public function itShouldThrowAnExceptionIfNoTokenIsProvided()
    {
        $this->expectException(UserNotDefinedException::class);
        $this->expectExceptionMessage('An error occurred');

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->andReturn(false);
        $this->jwt->shouldReceive('check')->never();
        $this->jwt->shouldReceive('getPayload->get')->never();
        $this->provider->shouldReceive('retrieveById')->never();

        $this->assertFalse($this->guard->check());
        $this->guard->userOrFail(); // throws the exception
    }

    /** @test */
    public function itShouldReturnATokenIfCredentialsAreOkAndUserIsFound()
    {
        $credentials = ['foo' => 'bar', 'baz' => 'bob'];
        $user = new LaravelUserStub();

        $this->provider->shouldReceive('retrieveByCredentials')
            ->once()
            ->with($credentials)
            ->andReturn($user);

        $this->provider->shouldReceive('validateCredentials')
            ->once()
            ->with($user, $credentials)
            ->andReturn(true);

        $this->jwt->shouldReceive('fromUser')
            ->once()
            ->with($user)
            ->andReturn('foo.bar.baz');

        $this->jwt->shouldReceive('setToken')
            ->once()
            ->with('foo.bar.baz')
            ->andReturnSelf();

        $this->jwt->shouldReceive('claims')
            ->once()
            ->with(['foo' => 'bar'])
            ->andReturnSelf();

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Attempting::class));

        if (class_exists('Illuminate\Auth\Events\Validated')) {
            $this->eventDispatcher->shouldReceive('dispatch')
                ->once()
                ->with(\Mockery::type('Illuminate\Auth\Events\Validated'));
        }

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Authenticated::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Login::class));

        $token = $this->guard->claims(['foo' => 'bar'])->attempt($credentials);

        $this->assertSame($this->guard->getLastAttempted(), $user);
        $this->assertSame($token, 'foo.bar.baz');
    }

    /** @test */
    public function itShouldReturnTrueIfCredentialsAreOkAndUserIsFoundWhenChoosingNotToLogin()
    {
        $credentials = ['foo' => 'bar', 'baz' => 'bob'];
        $user = new LaravelUserStub();

        $this->provider->shouldReceive('retrieveByCredentials')
            ->twice()
            ->with($credentials)
            ->andReturn($user);

        $this->provider->shouldReceive('validateCredentials')
            ->twice()
            ->with($user, $credentials)
            ->andReturn(true);

        $this->eventDispatcher->shouldReceive('dispatch')
            ->twice()
            ->with(\Mockery::type(Attempting::class));

        if (class_exists('Illuminate\Auth\Events\Validated')) {
            $this->eventDispatcher->shouldReceive('dispatch')
                ->twice()
                ->with(\Mockery::type('Illuminate\Auth\Events\Validated'));
        }

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Authenticated::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Login::class));

        $this->assertTrue($this->guard->attempt($credentials, false)); // once
        $this->assertTrue($this->guard->validate($credentials)); // twice
    }

    /** @test */
    public function itShouldReturnFalseIfCredentialsAreInvalid()
    {
        $credentials = ['foo' => 'bar', 'baz' => 'bob'];
        $user = new LaravelUserStub();

        $this->provider->shouldReceive('retrieveByCredentials')
            ->once()
            ->with($credentials)
            ->andReturn($user);

        $this->provider->shouldReceive('validateCredentials')
            ->once()
            ->with($user, $credentials)
            ->andReturn(false);

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Attempting::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Failed::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Authenticated::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Login::class));

        $this->assertFalse($this->guard->attempt($credentials));
    }

    /** @test */
    public function itShouldMagicallyCallTheJwtInstance()
    {
        $this->jwt->shouldReceive('factory')->andReturn(\Mockery::mock(Factory::class));
        $this->assertInstanceOf(Factory::class, $this->guard->factory());
    }

    /** @test */
    public function itShouldLogoutTheUserByInvalidatingTheToken()
    {
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(true);
        $this->jwt->shouldReceive('invalidate')->once()->andReturn(true);
        $this->jwt->shouldReceive('unsetToken')->once();

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Authenticated::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Logout::class));

        $this->guard->logout();
        $this->assertNull($this->guard->getUser());
    }

    /** @test */
    public function itShouldRefreshTheToken()
    {
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(true);
        $this->jwt->shouldReceive('refresh')->once()->andReturn('foo.bar.baz');

        $this->assertSame($this->guard->refresh(), 'foo.bar.baz');
    }

    /** @test */
    public function itShouldInvalidateTheToken()
    {
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(true);
        $this->jwt->shouldReceive('invalidate')->once()->andReturn(true);

        $this->assertTrue($this->guard->invalidate());
    }

    /** @test */
    public function itShouldThrowAnExceptionIfThereIsNoTokenPresentWhenRequired()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('Token could not be parsed from the request.');

        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn(false);
        $this->jwt->shouldReceive('refresh')->never();

        $this->guard->refresh();
    }

    /** @test */
    public function itShouldGenerateATokenById()
    {
        $user = new LaravelUserStub();

        $this->provider->shouldReceive('retrieveById')
            ->once()
            ->with(1)
            ->andReturn($user);

        $this->jwt->shouldReceive('fromUser')
            ->once()
            ->with($user)
            ->andReturn('foo.bar.baz');

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Authenticated::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Login::class));

        $this->assertSame('foo.bar.baz', $this->guard->tokenById(1));
    }

    /** @test */
    public function itShouldNotGenerateATokenById()
    {
        $this->provider->shouldReceive('retrieveById')
            ->once()
            ->with(1)
            ->andReturn(null);

        $this->assertNull($this->guard->tokenById(1));
    }

    /** @test */
    public function itShouldAuthenticateTheUserByCredentialsAndReturnTrueIfValid()
    {
        $credentials = ['foo' => 'bar', 'baz' => 'bob'];
        $user = new LaravelUserStub();

        $this->provider->shouldReceive('retrieveByCredentials')
            ->once()
            ->with($credentials)
            ->andReturn($user);

        $this->provider->shouldReceive('validateCredentials')
            ->once()
            ->with($user, $credentials)
            ->andReturn(true);

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Attempting::class));

        if (class_exists('Illuminate\Auth\Events\Validated')) {
            $this->eventDispatcher->shouldReceive('dispatch')
                ->once()
                ->with(\Mockery::type('Illuminate\Auth\Events\Validated'));
        }

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Authenticated::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->never()
            ->with(\Mockery::type(Login::class));

        $this->assertTrue($this->guard->once($credentials));
    }

    /** @test */
    public function itShouldAttemptToAuthenticateTheUserByCredentialsAndReturnFalseIfInvalid()
    {
        $credentials = ['foo' => 'bar', 'baz' => 'bob'];
        $user = new LaravelUserStub();

        $this->provider->shouldReceive('retrieveByCredentials')
            ->once()
            ->with($credentials)
            ->andReturn($user);

        $this->provider->shouldReceive('validateCredentials')
            ->once()
            ->with($user, $credentials)
            ->andReturn(false);

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Attempting::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Failed::class));

        $this->assertFalse($this->guard->once($credentials));
    }

    /** @test */
    public function itShouldAuthenticateTheUserByIdAndReturnBoolean()
    {
        $user = new LaravelUserStub();

        $this->provider->shouldReceive('retrieveById')
            ->twice()
            ->with(1)
            ->andReturn($user);

        $this->eventDispatcher->shouldReceive('dispatch')
            ->twice()
            ->with(\Mockery::type(Authenticated::class));

        $this->assertTrue($this->guard->onceUsingId(1)); // once
        $this->assertTrue($this->guard->byId(1)); // twice
    }

    /** @test */
    public function itShouldNotAuthenticateTheUserByIdAndReturnFalse()
    {
        $this->provider->shouldReceive('retrieveById')
            ->twice()
            ->with(1)
            ->andReturn(null);

        $this->assertFalse($this->guard->onceUsingId(1)); // once
        $this->assertFalse($this->guard->byId(1)); // twice
    }

    /** @test */
    public function itShouldCreateATokenFromAUserObject()
    {
        $user = new LaravelUserStub();

        $this->jwt->shouldReceive('fromUser')
            ->once()
            ->with($user)
            ->andReturn('foo.bar.baz');

        $this->jwt->shouldReceive('setToken')
            ->once()
            ->with('foo.bar.baz')
            ->andReturnSelf();

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Authenticated::class));

        $this->eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(Login::class));

        $token = $this->guard->login($user);

        $this->assertSame('foo.bar.baz', $token);
    }

    /** @test */
    public function itShouldGetThePayload()
    {
        $this->jwt->shouldReceive('setRequest')->andReturn($this->jwt);
        $this->jwt->shouldReceive('getToken')->once()->andReturn('foo.bar.baz');
        $this->jwt->shouldReceive('getPayload')->once()->andReturn(\Mockery::mock(Payload::class));
        $this->assertInstanceOf(Payload::class, $this->guard->payload());
    }

    /** @test */
    public function itShouldBeMacroable()
    {
        $this->guard->macro('foo', fn () => 'bar');

        $this->assertEquals('bar', $this->guard->foo());
    }
}
