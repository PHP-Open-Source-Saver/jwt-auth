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

namespace PHPOpenSourceSaver\JWTAuth\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Namshi\JOSE\JWS;
use PHPOpenSourceSaver\JWTAuth\Blacklist;
use PHPOpenSourceSaver\JWTAuth\Claims\Factory as ClaimFactory;
use PHPOpenSourceSaver\JWTAuth\Console\JWTGenerateCertCommand;
use PHPOpenSourceSaver\JWTAuth\Console\JWTGenerateSecretCommand;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Auth;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\JWT as JWTContract;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\Storage;
use PHPOpenSourceSaver\JWTAuth\Factory;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\AuthenticateAndRenew;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Check;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\RefreshToken;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\AuthHeaders;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\InputSource;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\QueryString;
use PHPOpenSourceSaver\JWTAuth\JWT;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use PHPOpenSourceSaver\JWTAuth\Manager;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci;
use PHPOpenSourceSaver\JWTAuth\Providers\JWT\Namshi;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;

abstract class AbstractServiceProvider extends ServiceProvider
{
    /**
     * The middleware aliases.
     */
    protected array $middlewareAliases = [
        'jwt.auth' => Authenticate::class,
        'jwt.check' => Check::class,
        'jwt.refresh' => RefreshToken::class,
        'jwt.renew' => AuthenticateAndRenew::class,
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    abstract public function boot();

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerAliases();

        $this->registerJWTProvider();
        $this->registerAuthProvider();
        $this->registerStorageProvider();
        $this->registerJWTBlacklist();

        $this->registerManager();
        $this->registerTokenParser();

        $this->registerJWT();
        $this->registerJWTAuth();
        $this->registerPayloadValidator();
        $this->registerClaimFactory();
        $this->registerPayloadFactory();
        $this->registerJWTCommands();

        $this->commands([
            'tymon.jwt.secret',
            'tymon.jwt.cert',
        ]);
    }

    /**
     * Extend Laravel's Auth.
     *
     * @return void
     */
    protected function extendAuthGuard()
    {
        $this->app['auth']->extend('jwt', function ($app, $name, array $config) {
            $guard = new JWTGuard(
                $app['tymon.jwt'],
                $app['auth']->createUserProvider($config['provider']),
                $app['request'],
                $app['events']
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    /**
     * Bind some aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->alias('tymon.jwt', JWT::class);
        $this->app->alias('tymon.jwt.auth', JWTAuth::class);
        $this->app->alias('tymon.jwt.provider.jwt', JWTContract::class);
        $this->app->alias('tymon.jwt.provider.jwt.namshi', Namshi::class);
        $this->app->alias('tymon.jwt.provider.jwt.lcobucci', Lcobucci::class);
        $this->app->alias('tymon.jwt.provider.auth', Auth::class);
        $this->app->alias('tymon.jwt.provider.storage', Storage::class);
        $this->app->alias('tymon.jwt.manager', Manager::class);
        $this->app->alias('tymon.jwt.blacklist', Blacklist::class);
        $this->app->alias('tymon.jwt.payload.factory', Factory::class);
        $this->app->alias('tymon.jwt.validators.payload', PayloadValidator::class);
    }

    /**
     * Register the bindings for the JSON Web Token provider.
     *
     * @return void
     */
    protected function registerJWTProvider()
    {
        $this->registerNamshiProvider();
        $this->registerLcobucciProvider();

        $this->app->singleton('tymon.jwt.provider.jwt', fn ($app) => $this->getConfigInstance($app, 'providers.jwt'));
    }

    /**
     * Register the bindings for the Namshi JWT provider.
     *
     * @return void
     */
    protected function registerNamshiProvider()
    {
        $this->app->singleton('tymon.jwt.provider.jwt.namshi', fn ($app) => new Namshi(
            new JWS(['typ' => 'JWT', 'alg' => $app->make('config')->get('jwt.algo')]),
            $app->make('config')->get('jwt.secret'),
            $app->make('config')->get('jwt.algo'),
            $app->make('config')->get('jwt.keys')
        ));
    }

    /**
     * Register the bindings for the Lcobucci JWT provider.
     *
     * @return void
     */
    protected function registerLcobucciProvider()
    {
        $this->app->singleton('tymon.jwt.provider.jwt.lcobucci', fn ($app) => new Lcobucci(
            $app->make('config')->get('jwt.secret'),
            $app->make('config')->get('jwt.algo'),
            $app->make('config')->get('jwt.keys')
        ));
    }

    /**
     * Register the bindings for the Auth provider.
     *
     * @return void
     */
    protected function registerAuthProvider()
    {
        $this->app->singleton('tymon.jwt.provider.auth', fn ($app) => $this->getConfigInstance($app, 'providers.auth'));
    }

    /**
     * Register the bindings for the Storage provider.
     *
     * @return void
     */
    protected function registerStorageProvider()
    {
        $this->app->singleton('tymon.jwt.provider.storage', fn ($app) => $this->getConfigInstance($app, 'providers.storage'));
    }

    /**
     * Register the bindings for the JWT Manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('tymon.jwt.manager', function ($app) {
            $instance = new Manager(
                $app['tymon.jwt.provider.jwt'],
                $app['tymon.jwt.blacklist'],
                $app['tymon.jwt.payload.factory']
            );

            return $instance->setBlacklistEnabled((bool) $app->make('config')->get('jwt.blacklist_enabled'))
                ->setPersistentClaims($app->make('config')->get('jwt.persistent_claims'))
                ->setBlackListExceptionEnabled((bool) $app->make('config')->get('jwt.show_black_list_exception', 0));
        });
    }

    /**
     * Register the bindings for the Token Parser.
     *
     * @return void
     */
    protected function registerTokenParser()
    {
        $this->app->singleton('tymon.jwt.parser', function ($app) {
            $parser = new Parser(
                $app['request'],
                [
                    new AuthHeaders(),
                    new QueryString(),
                    new InputSource(),
                ]
            );

            $app->refresh('request', $parser, 'setRequest');

            return $parser;
        });
    }

    /**
     * Register the bindings for the main JWT class.
     *
     * @return void
     */
    protected function registerJWT()
    {
        $this->app->singleton('tymon.jwt', fn ($app) => (new JWT(
            $app['tymon.jwt.manager'],
            $app['tymon.jwt.parser']
        ))->lockSubject($app->make('config')->get('jwt.lock_subject')));
    }

    /**
     * Register the bindings for the main JWTAuth class.
     *
     * @return void
     */
    protected function registerJWTAuth()
    {
        $this->app->singleton('tymon.jwt.auth', fn ($app) => (new JWTAuth(
            $app['tymon.jwt.manager'],
            $app['tymon.jwt.provider.auth'],
            $app['tymon.jwt.parser']
        ))->lockSubject($app->make('config')->get('jwt.lock_subject')));
    }

    /**
     * Register the bindings for the Blacklist.
     *
     * @return void
     */
    protected function registerJWTBlacklist()
    {
        $this->app->singleton('tymon.jwt.blacklist', function ($app) {
            $instance = new Blacklist($app['tymon.jwt.provider.storage']);

            return $instance->setGracePeriod($app->make('config')->get('jwt.blacklist_grace_period'))
                            ->setRefreshTTL($app->make('config')->get('jwt.refresh_ttl'));
        });
    }

    /**
     * Register the bindings for the payload validator.
     *
     * @return void
     */
    protected function registerPayloadValidator()
    {
        $this->app->singleton('tymon.jwt.validators.payload', fn ($app) => (new PayloadValidator())
            ->setRefreshTTL($app->make('config')->get('jwt.refresh_ttl'))
            ->setRequiredClaims($app->make('config')->get('jwt.required_claims')));
    }

    /**
     * Register the bindings for the Claim Factory.
     *
     * @return void
     */
    protected function registerClaimFactory()
    {
        $this->app->singleton('tymon.jwt.claim.factory', function ($app) {
            $factory = new ClaimFactory($app['request']);
            $app->refresh('request', $factory, 'setRequest');

            return $factory->setTTL($app->make('config')->get('jwt.ttl'))
                           ->setLeeway($app->make('config')->get('jwt.leeway'));
        });
    }

    /**
     * Register the bindings for the Payload Factory.
     *
     * @return void
     */
    protected function registerPayloadFactory()
    {
        $this->app->singleton('tymon.jwt.payload.factory', fn ($app) => new Factory(
            $app['tymon.jwt.claim.factory'],
            $app['tymon.jwt.validators.payload']
        ));
    }

    /**
     * Register the Artisan command.
     *
     * @return void
     */
    protected function registerJWTCommands()
    {
        $this->app->singleton('tymon.jwt.secret', fn () => new JWTGenerateSecretCommand());
        $this->app->singleton('tymon.jwt.cert', fn () => new JWTGenerateCertCommand());
    }

    /**
     * Get an instantiable configuration instance.
     *
     * @param Application $app
     * @param string      $key
     *
     * @return mixed
     */
    protected function getConfigInstance($app, $key)
    {
        $instance = $app->make('config')->get('jwt.'.$key);

        if (is_string($instance)) {
            return $this->app->make($instance);
        }

        return $instance;
    }
}
