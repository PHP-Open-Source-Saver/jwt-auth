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

use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Http\Parser\Parser;
use PHPOpenSourceSaver\JWTAuth\Support\CustomClaims;

class JWT
{
    use CustomClaims;

    /**
     * The authentication manager.
     *
     * @var Manager
     */
    protected $manager;

    /**
     * The HTTP parser.
     *
     * @var Parser
     */
    protected $parser;

    /**
     * The token.
     *
     * @var Token|null
     */
    protected $token;

    /**
     * Lock the subject.
     *
     * @var bool
     */
    protected $lockSubject = true;

    /**
     * JWT constructor.
     *
     * @return void
     */
    public function __construct(Manager $manager, Parser $parser)
    {
        $this->manager = $manager;
        $this->parser = $parser;
    }

    /**
     * Generate a token for a given subject.
     *
     * @return string
     */
    public function fromSubject(JWTSubject $subject)
    {
        $payload = $this->makePayload($subject);

        return $this->manager->encode($payload)->get();
    }

    /**
     * Alias to generate a token for a given user.
     *
     * @return string
     */
    public function fromUser(JWTSubject $user)
    {
        return $this->fromSubject($user);
    }

    /**
     * Refresh an expired token.
     *
     * @param bool $forceForever
     * @param bool $resetClaims
     *
     * @return string
     */
    public function refresh($forceForever = false, $resetClaims = false)
    {
        $this->requireToken();

        return $this->manager->customClaims($this->getCustomClaims())
            ->refresh($this->token, $forceForever, $resetClaims)
            ->get();
    }

    /**
     * Invalidate a token (add it to the blacklist).
     *
     * @param bool $forceForever
     *
     * @return $this
     */
    public function invalidate($forceForever = false)
    {
        $this->requireToken();

        $this->manager->invalidate($this->token, $forceForever);

        return $this;
    }

    /**
     * Alias to get the payload, and as a result checks that
     * the token is valid i.e. not expired or blacklisted.
     *
     * @return Payload
     *
     * @throws JWTException
     */
    public function checkOrFail()
    {
        return $this->getPayload();
    }

    /**
     * Check that the token is valid.
     *
     * @param bool $getPayload
     *
     * @return Payload|bool
     */
    public function check($getPayload = false)
    {
        try {
            $payload = $this->checkOrFail();
        } catch (JWTException $e) {
            return false;
        }

        return $getPayload ? $payload : true;
    }

    /**
     * Get the token.
     *
     * @return Token|null
     */
    public function getToken()
    {
        if (null === $this->token) {
            try {
                $this->parseToken();
            } catch (JWTException $e) {
                $this->token = null;
            }
        }

        return $this->token;
    }

    /**
     * Parse the token from the request.
     *
     * @return $this
     *
     * @throws JWTException
     */
    public function parseToken()
    {
        if (!$token = $this->parser->parseToken()) {
            throw new JWTException('The token could not be parsed from the request');
        }

        return $this->setToken($token);
    }

    /**
     * Get the raw Payload instance.
     *
     * @return Payload
     */
    public function getPayload()
    {
        $this->requireToken();

        return $this->manager->decode($this->token);
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
     * Convenience method to get a claim value.
     *
     * @param string $claim
     */
    public function getClaim($claim)
    {
        return $this->payload()->get($claim);
    }

    /**
     * Create a Payload instance.
     *
     * @return Payload
     */
    public function makePayload(JWTSubject $subject)
    {
        return $this->factory()->customClaims($this->getClaimsArray($subject))->make();
    }

    /**
     * Build the claims array and return it.
     *
     * @return array
     */
    protected function getClaimsArray(JWTSubject $subject)
    {
        return array_merge(
            $this->getClaimsForSubject($subject),
            $subject->getJWTCustomClaims(), // custom claims from JWTSubject method
            $this->customClaims // custom claims from inline setter
        );
    }

    /**
     * Get the claims associated with a given subject.
     *
     * @return array
     */
    protected function getClaimsForSubject(JWTSubject $subject)
    {
        return array_merge([
            'sub' => $subject->getJWTIdentifier(),
        ], $this->lockSubject ? ['prv' => $this->hashSubjectModel($subject)] : []);
    }

    /**
     * Hash the subject model and return it.
     *
     * @param string|object $model
     *
     * @return string
     */
    protected function hashSubjectModel($model)
    {
        return sha1(is_object($model) ? get_class($model) : $model);
    }

    /**
     * Check if the subject model matches the one saved in the token.
     *
     * @param string|object $model
     *
     * @return bool
     */
    public function checkSubjectModel($model)
    {
        if (($prv = $this->payload()->get('prv')) === null) {
            return true;
        }

        return $this->hashSubjectModel($model) === $prv;
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
        $this->token = $token instanceof Token ? $token : new Token($token);

        return $this;
    }

    /**
     * Unset the current token.
     *
     * @return $this
     */
    public function unsetToken()
    {
        $this->token = null;

        return $this;
    }

    /**
     * Ensure that a token is available.
     *
     * @return void
     *
     * @throws JWTException
     */
    protected function requireToken()
    {
        if (!$this->token) {
            throw new JWTException('A token is required');
        }
    }

    /**
     * Set the request instance.
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->parser->setRequest($request);

        return $this;
    }

    /**
     * Set whether the subject should be "locked".
     *
     * @param bool $lock
     *
     * @return $this
     */
    public function lockSubject($lock)
    {
        $this->lockSubject = $lock;

        return $this;
    }

    /**
     * Get the Manager instance.
     *
     * @return Manager
     */
    public function manager()
    {
        return $this->manager;
    }

    /**
     * Get the Parser instance.
     *
     * @return Parser
     */
    public function parser()
    {
        return $this->parser;
    }

    /**
     * Get the Payload Factory.
     *
     * @return Factory
     */
    public function factory()
    {
        return $this->manager->getPayloadFactory();
    }

    /**
     * Get the Blacklist.
     *
     * @return Blacklist
     */
    public function blacklist()
    {
        return $this->manager->getBlacklist();
    }

    /**
     * Magically call the JWT Manager.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->manager, $method)) {
            return call_user_func_array([$this->manager, $method], $parameters);
        }

        throw new \BadMethodCallException("Method [$method] does not exist.");
    }
}
