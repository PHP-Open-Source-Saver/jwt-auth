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

namespace PHPOpenSourceSaver\JWTAuth\Test\Providers;

use Illuminate\Support\Facades\Event;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;
use Orchestra\Testbench\TestCase;
use PHPOpenSourceSaver\JWTAuth\Blacklist;
use PHPOpenSourceSaver\JWTAuth\Claims\Factory as ClaimFactory;
use PHPOpenSourceSaver\JWTAuth\Console\JWTGenerateCertCommand;
use PHPOpenSourceSaver\JWTAuth\Console\JWTGenerateSecretCommand;
use PHPOpenSourceSaver\JWTAuth\Contracts\Http\Parser;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Auth;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\JWT as JWTContract;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Storage;
use PHPOpenSourceSaver\JWTAuth\Factory;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\AuthenticateAndRenew;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Check;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken;
use PHPOpenSourceSaver\JWTAuth\JWT;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use PHPOpenSourceSaver\JWTAuth\Manager;
use PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Namshi;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Provider;
use PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;

class LaravelServiceProviderTest extends TestCase
{
    private array $keys = ['private' => 'foo', 'public' => 'bar', 'passphrase' => 'baz'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('jwt.secret', 'some-secret');
        $this->app['config']->set('jwt.algo', 'ES512');
        $this->app['config']->set('jwt.keys', $this->keys);
        $this->app['config']->set('auth.guards.jwt', [
            'driver' => 'jwt',
            'provider' => 'users',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [LaravelServiceProvider::class];
    }

    public function testBoot()
    {
        $this->assertSame([
            'jwt.auth' => Authenticate::class,
            'jwt.check' => Check::class,
            'jwt.refresh' => RefreshToken::class,
            'jwt.renew' => AuthenticateAndRenew::class,
        ], $this->app['router']->getMiddleware());

        $this->app['config']['auth.guards.jwt'] = [
            'driver' => 'jwt',
            'provider' => 'users',
        ];

        $instance = $this->app['auth']->guard('jwt');
        $this->assertInstanceOf(JWTGuard::class, $instance);

        $parsers = $this->app['tymon.jwt.parser']->getChain();
        $this->assertCount(5, $parsers);
        $this->assertContainsOnlyInstancesOf(Parser::class, $parsers);

        $this->assertCount(0, Event::getListeners(RequestReceived::class));
        $this->assertCount(0, Event::getListeners(TaskReceived::class));
        $this->assertCount(0, Event::getListeners(TickReceived::class));

        $_SERVER['LARAVEL_OCTANE'] = true;
        $this->refreshApplication();
        $this->assertCount(1, Event::getListeners(RequestReceived::class));
        $this->assertCount(1, Event::getListeners(TaskReceived::class));
        $this->assertCount(1, Event::getListeners(TickReceived::class));
    }

    public function testRegisterAliases()
    {
        $this->assertInstanceOf(JWT::class, $this->app->make('tymon.jwt'));
        $this->assertInstanceOf(JWTAuth::class, $this->app->make('tymon.jwt.auth'));
        $this->assertInstanceOf(JWTContract::class, $this->app->make('tymon.jwt.provider.jwt'));
        $this->assertInstanceOf(Namshi::class, $this->app->make('tymon.jwt.provider.jwt.namshi'));
        $this->assertInstanceOf(Lcobucci::class, $this->app->make('tymon.jwt.provider.jwt.lcobucci'));
        $this->assertInstanceOf(Auth::class, $this->app->make('tymon.jwt.provider.auth'));
        $this->assertInstanceOf(Storage::class, $this->app->make('tymon.jwt.provider.storage'));
        $this->assertInstanceOf(Manager::class, $this->app->make('tymon.jwt.manager'));
        $this->assertInstanceOf(Blacklist::class, $this->app->make('tymon.jwt.blacklist'));
        $this->assertInstanceOf(Factory::class, $this->app->make('tymon.jwt.payload.factory'));
        $this->assertInstanceOf(PayloadValidator::class, $this->app->make('tymon.jwt.validators.payload'));
    }

    public function testRegisterJwtProviders()
    {
        /** @var Provider $jwtProvider */
        $jwtProvider = $this->app->make('tymon.jwt.provider.jwt');
        $this->assertInstanceOf(Lcobucci::class, $jwtProvider);
        $this->assertSame('some-secret', $jwtProvider->getSecret());
        $this->assertSame('ES512', $jwtProvider->getAlgo());
        $this->assertSame($this->keys, $jwtProvider->getKeys());

        $this->app['config']->set('jwt.providers.jwt', Namshi::class);
        $this->app->forgetInstance('tymon.jwt.provider.jwt');
        $jwtProvider = $this->app->make('tymon.jwt.provider.jwt');
        $this->assertInstanceOf(Namshi::class, $jwtProvider);
    }

    public function testRegisterLcobucciProvider()
    {
        /** @var Provider $jwtProvider */
        $jwtProvider = $this->app->make('tymon.jwt.provider.jwt.lcobucci');
        $this->assertInstanceOf(Lcobucci::class, $jwtProvider);
        $this->assertSame('some-secret', $jwtProvider->getSecret());
        $this->assertSame('ES512', $jwtProvider->getAlgo());
        $this->assertSame($this->keys, $jwtProvider->getKeys());
    }

    public function testRegisterNamshiProvider()
    {
        /** @var Provider $jwtProvider */
        $jwtProvider = $this->app->make('tymon.jwt.provider.jwt.namshi');
        $this->assertInstanceOf(Namshi::class, $jwtProvider);
        $this->assertSame('some-secret', $jwtProvider->getSecret());
        $this->assertSame('ES512', $jwtProvider->getAlgo());
        $this->assertSame($this->keys, $jwtProvider->getKeys());
    }

    public function testRegisterAuthProvider()
    {
        /** @var Auth $authProvider */
        $authProvider = $this->app->make('tymon.jwt.provider.auth');
        $this->assertInstanceOf(Illuminate::class, $authProvider);
    }

    public function testRegisterStorageProvider()
    {
        /** @var Storage $storageProvider */
        $storageProvider = $this->app->make('tymon.jwt.provider.storage');
        $this->assertInstanceOf(
            \PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate::class,
            $storageProvider,
        );
    }

    public function testRegisterManager()
    {
        /** @var Manager $manager */
        $manager = $this->app->make('tymon.jwt.manager');
        $this->assertInstanceOf(Manager::class, $manager);
        $this->assertTrue($manager->getBlackListExceptionEnabled());

        $this->app['config']->set('jwt.show_black_list_exception', false);
        $this->app->forgetInstance('tymon.jwt.manager');
        $manager = $this->app->make('tymon.jwt.manager');
        $this->assertFalse($manager->getBlackListExceptionEnabled());
    }

    public function testRegisterTokenParser()
    {
        $this->app->forgetInstance('tymon.jwt.parser');

        /** @var \PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser $parser */
        $parser = $this->app->make('tymon.jwt.parser');
        $parsers = $parser->getChain();

        $this->assertInstanceOf(
            \PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser::class,
            $parser,
        );
        $this->assertCount(3, $parsers);
        $this->assertContainsOnlyInstancesOf(Parser::class, $parsers);
    }

    public function testRegisterJWT()
    {
        /** @var JWT $jwt */
        $jwt = $this->app->make('tymon.jwt');
        $this->assertInstanceOf(JWT::class, $jwt);
    }

    public function testRegisterJWTAuth()
    {
        /** @var JWTAuth $jwt */
        $jwt = $this->app->make('tymon.jwt.auth');
        $this->assertInstanceOf(JWTAuth::class, $jwt);
    }

    public function testRegisterJWTBlacklist()
    {
        /** @var Blacklist $blacklist */
        $blacklist = $this->app->make('tymon.jwt.blacklist');
        $this->assertInstanceOf(Blacklist::class, $blacklist);
        $this->assertSame(0, $blacklist->getGracePeriod());
        $this->assertSame(20160, $blacklist->getRefreshTTL());

        $this->app['config']->set('jwt.blacklist_grace_period', 30);
        $this->app['config']->set('jwt.refresh_ttl', 45);

        $this->app->forgetInstance('tymon.jwt.blacklist');
        $blacklist = $this->app->make('tymon.jwt.blacklist');
        $this->assertSame(30, $blacklist->getGracePeriod());
        $this->assertSame(45, $blacklist->getRefreshTTL());
    }

    public function testRegisterPayloadValidator()
    {
        /** @var PayloadValidator $validator */
        $validator = $this->app->make('tymon.jwt.validators.payload');
        $this->assertInstanceOf(PayloadValidator::class, $validator);
    }

    public function testRegisterClaimFactory()
    {
        /** @var ClaimFactory $factory */
        $factory = $this->app->make('tymon.jwt.claim.factory');
        $this->assertInstanceOf(ClaimFactory::class, $factory);
        $this->assertSame(60, $factory->getTTL());
        $this->app['config']->set('jwt.ttl', 45);

        $this->app->forgetInstance('tymon.jwt.claim.factory');
        $factory = $this->app->make('tymon.jwt.claim.factory');
        $this->assertSame(45, $factory->getTTL());
    }

    public function testRegisterPayloadFactory()
    {
        /** @var Factory $factory */
        $factory = $this->app->make('tymon.jwt.payload.factory');
        $this->assertInstanceOf(Factory::class, $factory);
    }

    public function testRegisterJWTGenerateSecretCommand()
    {
        /** @var Factory $factory */
        $factory = $this->app->make('tymon.jwt.secret');
        $this->assertInstanceOf(JWTGenerateSecretCommand::class, $factory);
    }

    public function testRegisterJWTGenerateCertCommand()
    {
        /** @var Factory $factory */
        $factory = $this->app->make('tymon.jwt.cert');
        $this->assertInstanceOf(JWTGenerateCertCommand::class, $factory);
    }

    public function testGuardHasOwnTTL()
    {
        // with custom ttl
        $this->app['config']->set('auth.guards.custom-ttl-guard', [
            'driver' => 'jwt',
            'provider' => 'users',
            'ttl' => 120,
        ]);

        $customInstance = $this->app['auth']->guard('custom-ttl-guard');
        $this->assertInstanceOf(JWTGuard::class, $customInstance);

        $this->assertEquals(120, $customInstance->getTTL());

        // with null ttl
        $this->app['config']->set('auth.guards.null-ttl-guard', [
            'driver' => 'jwt',
            'provider' => 'users',
            'ttl' => null,
        ]);

        $notNullInstance = $this->app['auth']->guard('null-ttl-guard');
        $this->assertInstanceOf(JWTGuard::class, $notNullInstance);

        $this->assertNull($notNullInstance->getTTL());

        // other guards' ttl should not be affected and should use default global ttl
        $jwtInstance = $this->app['auth']->guard('jwt');
        $this->assertInstanceOf(JWTGuard::class, $jwtInstance);

        $this->assertEquals(
            $this->app['config']->get('jwt.ttl'),
            $jwtInstance->getTTL(),
        );
    }
}
