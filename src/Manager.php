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

use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\JWT as JWTContract;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Support\CustomClaims;
use PHPOpenSourceSaver\JWTAuth\Support\RefreshFlow;

class Manager
{
    use CustomClaims;
    use RefreshFlow;

    /**
     * The provider.
     *
     * @var JWTContract
     */
    protected $provider;

    /**
     * The blacklist.
     *
     * @var Blacklist
     */
    protected $blacklist;

    /**
     * the payload factory.
     *
     * @var Factory
     */
    protected $payloadFactory;

    /**
     * The blacklist flag.
     *
     * @var bool
     */
    protected $blacklistEnabled = true;

    /**
     * the persistent claims.
     *
     * @var array
     */
    protected $persistentClaims = [];

    /**
     * @var bool
     */
    protected $showBlackListException = true;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct(JWTContract $provider, Blacklist $blacklist, Factory $payloadFactory)
    {
        $this->provider = $provider;
        $this->blacklist = $blacklist;
        $this->payloadFactory = $payloadFactory;
    }

    /**
     * Encode a Payload and return the Token.
     *
     * @return Token
     */
    public function encode(Payload $payload)
    {
        $token = $this->provider->encode($payload->get());

        return new Token($token);
    }

    /**
     * Decode a Token and return the Payload.
     *
     * @param bool $checkBlacklist
     *
     * @return Payload
     *
     * @throws \PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException
     */
    public function decode(Token $token, $checkBlacklist = true)
    {
        $payloadArray = $this->provider->decode($token->get());

        $payload = $this->payloadFactory
            ->setRefreshFlow($this->refreshFlow)
            ->customClaims($payloadArray)
            ->make();

        if (
            $checkBlacklist &&
            $this->blacklistEnabled &&
            $this->getBlackListExceptionEnabled() &&
            $this->blacklist->has($payload)
        ) {
            throw new TokenBlacklistedException('The token has been blacklisted');
        }

        return $payload;
    }

    /**
     * Refresh a Token and return a new Token.
     *
     * @param bool $forceForever
     * @param bool $resetClaims
     *
     * @return Token
     */
    public function refresh(Token $token, $forceForever = false, $resetClaims = false)
    {
        $this->setRefreshFlow();

        $claims = $this->buildRefreshClaims($this->decode($token));

        if ($this->blacklistEnabled) {
            // Invalidate old token
            $this->invalidate($token, $forceForever);
        }

        // Return the new token
        return $this->encode(
            $this->payloadFactory->customClaims($claims)->make($resetClaims)
        );
    }

    /**
     * Invalidate a Token by adding it to the blacklist.
     *
     * @param bool $forceForever
     *
     * @return bool
     *
     * @throws JWTException
     */
    public function invalidate(Token $token, $forceForever = false)
    {
        if (!$this->blacklistEnabled) {
            throw new JWTException('You must have the blacklist enabled to invalidate a token.');
        }

        return call_user_func(
            [$this->blacklist, $forceForever ? 'addForever' : 'add'],
            $this->decode($token, false)
        );
    }

    /**
     * Build the claims to go into the refreshed token.
     *
     * @return array
     */
    protected function buildRefreshClaims(Payload $payload)
    {
        // Get the claims to be persisted from the payload
        $persistentClaims = collect($payload->toArray())
            ->only($this->persistentClaims)
            ->toArray();

        // persist the relevant claims
        return array_merge(
            $this->customClaims,
            $persistentClaims,
            [
                'sub' => $payload['sub'],
                'iat' => $payload['iat'],
            ]
        );
    }

    /**
     * Get the Payload Factory instance.
     *
     * @return Factory
     */
    public function getPayloadFactory()
    {
        return $this->payloadFactory;
    }

    /**
     * Get the JWTProvider instance.
     *
     * @return JWTContract
     */
    public function getJWTProvider()
    {
        return $this->provider;
    }

    /**
     * Get the Blacklist instance.
     *
     * @return Blacklist
     */
    public function getBlacklist()
    {
        return $this->blacklist;
    }

    /**
     * Set whether the blacklist is enabled.
     *
     * @param bool $enabled
     *
     * @return $this
     */
    public function setBlacklistEnabled($enabled)
    {
        $this->blacklistEnabled = $enabled;

        return $this;
    }

    /**
     * Configuration to set up if show the TokenBlacklistedException
     * can be throwable or not.
     *
     * @param bool $showBlackListException
     *
     * @removed this
     */
    public function setBlackListExceptionEnabled($showBlackListException = true)
    {
        $this->showBlackListException = $showBlackListException;

        return $this;
    }

    /**
     * Get if the blacklist instance is enabled.
     *
     * @return bool
     */
    public function getBlackListExceptionEnabled()
    {
        return $this->showBlackListException;
    }

    /**
     * Set the claims to be persisted when refreshing a token.
     *
     * @return $this
     */
    public function setPersistentClaims(array $claims)
    {
        $this->persistentClaims = $claims;

        return $this;
    }
}
