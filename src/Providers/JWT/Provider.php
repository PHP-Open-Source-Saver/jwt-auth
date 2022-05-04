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

namespace PHPOpenSourceSaver\JWTAuth\Providers\JWT;

use Illuminate\Support\Arr;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\SecretMissingException;

abstract class Provider
{
    /**
     * The secret.
     */
    protected ?string $secret;

    /**
     * The array of keys.
     */
    protected array $keys;

    /**
     * The used algorithm.
     */
    protected string $algo;

    /**
     * Constructor.
     *
     * @param string $secret
     * @param string $algo
     *
     * @return void
     */
    public function __construct($secret, $algo, array $keys)
    {
        if (is_null($secret) && (is_null($keys['public']) || is_null($keys['private']))) {
            throw new SecretMissingException();
        }

        $this->secret = $secret;
        $this->algo = $algo;
        $this->keys = $keys;
    }

    /**
     * Set the algorithm used to sign the token.
     *
     * @param string $algo
     *
     * @return $this
     */
    public function setAlgo($algo)
    {
        $this->algo = $algo;

        return $this;
    }

    /**
     * Get the algorithm used to sign the token.
     *
     * @return string
     */
    public function getAlgo()
    {
        return $this->algo;
    }

    /**
     * Set the secret used to sign the token.
     *
     * @param string $secret
     *
     * @return $this
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * Get the secret used to sign the token.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set the keys used to sign the token.
     *
     * @return $this
     */
    public function setKeys(array $keys)
    {
        $this->keys = $keys;

        return $this;
    }

    /**
     * Get the array of keys used to sign tokens
     * with an asymmetric algorithm.
     *
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Get the public key used to sign tokens
     * with an asymmetric algorithm.
     *
     * @return resource|string
     */
    public function getPublicKey()
    {
        return Arr::get($this->keys, 'public');
    }

    /**
     * Get the private key used to sign tokens
     * with an asymmetric algorithm.
     *
     * @return resource|string
     */
    public function getPrivateKey()
    {
        return Arr::get($this->keys, 'private');
    }

    /**
     * Get the passphrase used to sign tokens
     * with an asymmetric algorithm.
     *
     * @return string
     */
    public function getPassphrase()
    {
        return Arr::get($this->keys, 'passphrase');
    }

    /**
     * Get the key used to sign the tokens.
     *
     * @return resource|string
     */
    protected function getSigningKey()
    {
        return $this->isAsymmetric() ? $this->getPrivateKey() : $this->getSecret();
    }

    /**
     * Get the key used to verify the tokens.
     *
     * @return resource|string
     */
    protected function getVerificationKey()
    {
        return $this->isAsymmetric() ? $this->getPublicKey() : $this->getSecret();
    }

    /**
     * Determine if the algorithm is asymmetric, and thus
     * requires a public/private key combo.
     *
     * @return bool
     *
     * @throws JWTException
     */
    abstract protected function isAsymmetric();
}
