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

namespace PHPOpenSourceSaver\JWTAuth;

use BadMethodCallException;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\UserNotDefinedException;

class JWTGuard implements Guard
{
    use GuardHelpers {
        setUser as guardHelperSetUser;
    }
    use Macroable {
        __call as macroCall;
    }

    /**
     * The user we last attempted to retrieve.
     *
     * @var Authenticatable
     */
    protected $lastAttempted;

    /**
     * The JWT instance.
     *
     * @var JWT
     */
    protected $jwt;

    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected $events;

    /**
     * The name of the Guard.
     *
     * @var string
     */
    protected $name = 'tymon.jwt';

    /**
     * Instantiate the class.
     *
     * @return void
     */
    public function __construct(JWT $jwt, UserProvider $provider, Request $request, Dispatcher $eventDispatcher)
    {
        $this->jwt = $jwt;
        $this->provider = $provider;
        $this->request = $request;
        $this->events = $eventDispatcher;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user()
    {
        if (null !== $this->user) {
            return $this->user;
        }

        if (
            $this->jwt->setRequest($this->request)->getToken() &&
            ($payload = $this->jwt->check(true)) &&
            $this->validateSubject()
        ) {
            return $this->user = $this->provider->retrieveById($payload['sub']);
        }
    }

    /**
     * Get the currently authenticated user or throws an exception.
     *
     * @return Authenticatable
     *
     * @throws UserNotDefinedException
     */
    public function userOrFail()
    {
        if (!$user = $this->user()) {
            throw new UserNotDefinedException();
        }

        return $user;
    }

    /**
     * Validate a user's credentials.
     *
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return (bool) $this->attempt($credentials, false);
    }

    /**
     * Attempt to authenticate the user using the given credentials and return the token.
     *
     * @param bool $login
     *
     * @return bool|string
     */
    public function attempt(array $credentials = [], $login = true)
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        $this->fireAttemptEvent($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            return $login ? $this->login($user) : true;
        }

        $this->fireFailedEvent($user, $credentials);

        return false;
    }

    /**
     * Create a token for a user.
     *
     * @return string
     */
    public function login(JWTSubject $user)
    {
        $token = $this->jwt->fromUser($user);
        $this->setToken($token)->setUser($user);

        $this->fireLoginEvent($user);

        return $token;
    }

    /**
     * Logout the user, thus invalidating the token.
     *
     * @param bool $forceForever
     *
     * @return void
     */
    public function logout($forceForever = false)
    {
        $this->requireToken()->invalidate($forceForever);

        $this->fireLogoutEvent($this->user);

        $this->user = null;
        $this->jwt->unsetToken();
    }

    /**
     * Refresh the token.
     *
     * @param bool $forceForever
     * @param bool $resetClaims
     *
     * @return string
     */
    public function refresh($forceForever = false, $resetClaims = false)
    {
        return $this->requireToken()->refresh($forceForever, $resetClaims);
    }

    /**
     * Invalidate the token.
     *
     * @param bool $forceForever
     *
     * @return JWT
     */
    public function invalidate($forceForever = false)
    {
        return $this->requireToken()->invalidate($forceForever);
    }

    /**
     * Create a new token by User id.
     *
     * @param mixed $id
     *
     * @return string|null
     */
    public function tokenById($id)
    {
        if ($user = $this->provider->retrieveById($id)) {
            return $this->jwt->fromUser($user);
        }
    }

    /**
     * Log a user into the application using their credentials.
     *
     * @return bool
     */
    public function once(array $credentials = [])
    {
        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttempted);

            return true;
        }

        return false;
    }

    /**
     * Log the given User into the application.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function onceUsingId($id)
    {
        if ($user = $this->provider->retrieveById($id)) {
            $this->setUser($user);

            return true;
        }

        return false;
    }

    /**
     * Alias for onceUsingId.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function byId($id)
    {
        return $this->onceUsingId($id);
    }

    /**
     * Add any custom claims.
     *
     * @return $this
     */
    public function claims(array $claims)
    {
        $this->jwt->claims($claims);

        return $this;
    }

    /**
     * Get the raw Payload instance.
     *
     * @return Payload
     */
    public function getPayload()
    {
        return $this->requireToken()->getPayload();
    }

    /**
     * Alias for getPayload().
     *
     * @return Payload
     */
    public function payload()
    {
        return $this->getPayload();
    }

    /**
     * Set the token.
     *
     * @param Token|string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->jwt->setToken($token);

        return $this;
    }

    /**
     * Set the token ttl.
     *
     * @param int|null $ttl
     *
     * @return $this
     */
    public function setTTL($ttl)
    {
        $this->jwt->factory()->setTTL($ttl);

        return $this;
    }

    /**
     * Get the user provider used by the guard.
     *
     * @return UserProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Set the user provider used by the guard.
     *
     * @return $this
     */
    public function setProvider(UserProvider $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Return the currently cached user.
     *
     * @return Authenticatable|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the current user.
     *
     * @return $this
     */
    public function setUser(Authenticatable $user)
    {
        $result = $this->guardHelperSetUser($user);

        $this->fireAuthenticatedEvent($user);

        return $result;
    }

    /**
     * Get the current request instance.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request ?: Request::createFromGlobals();
    }

    /**
     * Set the current request instance.
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get the last user we attempted to authenticate.
     *
     * @return Authenticatable
     */
    public function getLastAttempted()
    {
        return $this->lastAttempted;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param mixed $user
     * @param array $credentials
     *
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        $validated = null !== $user && $this->provider->validateCredentials($user, $credentials);

        if ($validated) {
            $this->fireValidatedEvent($user);
        }

        return $validated;
    }

    /**
     * Ensure the JWTSubject matches what is in the token.
     *
     * @return bool
     */
    protected function validateSubject()
    {
        // If the provider doesn't have the necessary method
        // to get the underlying model name then allow.
        if (!method_exists($this->provider, 'getModel')) {
            return true;
        }

        return $this->jwt->checkSubjectModel($this->provider->getModel());
    }

    /**
     * Ensure that a token is available in the request.
     *
     * @return JWT
     *
     * @throws \PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException
     */
    protected function requireToken()
    {
        if (!$this->jwt->setRequest($this->getRequest())->getToken()) {
            throw new JWTException('Token could not be parsed from the request.');
        }

        return $this->jwt;
    }

    /**
     * Fire the attempt event.
     *
     * @return void
     */
    protected function fireAttemptEvent(array $credentials)
    {
        $this->events->dispatch(new Attempting(
            $this->name,
            $credentials,
            false
        ));
    }

    /**
     * Fires the validated event.
     *
     * @param Authenticatable $user
     *
     * @return void
     */
    protected function fireValidatedEvent($user)
    {
        if (class_exists('Illuminate\Auth\Events\Validated')) {
            $this->events->dispatch(
                new \Illuminate\Auth\Events\Validated(
                    $this->name,
                    $user
                )
            );
        }
    }

    /**
     * Fire the failed authentication attempt event.
     *
     * @param Authenticatable|null $user
     *
     * @return void
     */
    protected function fireFailedEvent($user, array $credentials)
    {
        $this->events->dispatch(new Failed(
            $this->name,
            $user,
            $credentials
        ));
    }

    /**
     * Fire the authenticated event.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     *
     * @return void
     */
    protected function fireAuthenticatedEvent($user)
    {
        $this->events->dispatch(new Authenticated(
            $this->name,
            $user
        ));
    }

    /**
     * Fire the login event.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool                                       $remember
     *
     * @return void
     */
    protected function fireLoginEvent($user, $remember = false)
    {
        $this->events->dispatch(new Login(
            $this->name,
            $user,
            $remember
        ));
    }

    /**
     * Fire the logout event.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool                                       $remember
     *
     * @return void
     */
    protected function fireLogoutEvent($user, $remember = false)
    {
        $this->events->dispatch(new Logout(
            $this->name,
            $user
        ));
    }

    /**
     * Magically call the JWT instance.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->jwt, $method)) {
            return call_user_func_array([$this->jwt, $method], $parameters);
        }

        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
}
