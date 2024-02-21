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

use Illuminate\Support\Collection;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa;
use Lcobucci\JWT\Signer\Ecdsa\Sha256 as ES256;
use Lcobucci\JWT\Signer\Ecdsa\Sha384 as ES384;
use Lcobucci\JWT\Signer\Ecdsa\Sha512 as ES512;
use Lcobucci\JWT\Signer\Hmac\Sha256 as HS256;
use Lcobucci\JWT\Signer\Hmac\Sha384 as HS384;
use Lcobucci\JWT\Signer\Hmac\Sha512 as HS512;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RS256;
use Lcobucci\JWT\Signer\Rsa\Sha384 as RS384;
use Lcobucci\JWT\Signer\Rsa\Sha512 as RS512;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\JWT;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

class Lcobucci extends Provider implements JWT
{
    /**
     * The builder instance.
     *
     * @var Builder
     */
    protected $builder;

    /**
     * The configuration instance.
     *
     * @var Configuration
     */
    protected $config;

    /**
     * The Signer instance.
     *
     * @var Signer
     */
    protected $signer;

    /**
     * Create the Lcobucci provider.
     *
     * @param string        $secret
     * @param string        $algo
     * @param Configuration $config optional, to pass an existing configuration to be used
     *
     * @return void
     */
    public function __construct(
        $secret,
        $algo,
        array $keys,
        $config = null
    ) {
        parent::__construct($secret, $algo, $keys);

        $this->signer = $this->getSigner();

        if (!is_null($config)) {
            $this->config = $config;
        } elseif ($this->isAsymmetric()) {
            $this->config = Configuration::forAsymmetricSigner($this->signer, $this->getSigningKey(), $this->getVerificationKey());
        } else {
            $this->config = Configuration::forSymmetricSigner($this->signer, InMemory::plainText($this->getSecret()));
        }
        if (!count($this->config->validationConstraints())) {
            $this->config->setValidationConstraints(
                new SignedWith($this->signer, $this->getVerificationKey()),
            );
        }
    }

    /**
     * Gets the {@see $config} attribute.
     *
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Signers that this provider supports.
     *
     * @var array
     */
    protected $signers = [
        'HS256' => HS256::class,
        'HS384' => HS384::class,
        'HS512' => HS512::class,
        'RS256' => RS256::class,
        'RS384' => RS384::class,
        'RS512' => RS512::class,
        'ES256' => ES256::class,
        'ES384' => ES384::class,
        'ES512' => ES512::class,
    ];

    /**
     * Create a JSON Web Token.
     *
     * @return string
     *
     * @throws JWTException
     */
    public function encode(array $payload)
    {
        $this->builder = null;
        $this->builder = $this->config->builder();

        try {
            foreach ($payload as $key => $value) {
                $this->builder = $this->addClaim($key, $value);
            }

            return $this->builder->getToken($this->config->signer(), $this->config->signingKey())->toString();
        } catch (\Exception $e) {
            throw new JWTException('Could not create token: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Decode a JSON Web Token.
     *
     * @param string $token
     *
     * @return array
     *
     * @throws JWTException
     */
    public function decode($token)
    {
        try {
            $jwt = $this->config->parser()->parse($token);
        } catch (\Exception $e) {
            throw new TokenInvalidException('Could not decode token: '.$e->getMessage(), $e->getCode(), $e);
        }

        if (!$this->config->validator()->validate($jwt, ...$this->config->validationConstraints())) {
            throw new TokenInvalidException('Token Signature could not be verified.');
        }

        return (new Collection($jwt->claims()->all()))->map(function ($claim) {
            if (is_a($claim, \DateTimeImmutable::class)) {
                return $claim->getTimestamp();
            }
            if (is_object($claim) && method_exists($claim, 'getValue')) {
                return $claim->getValue();
            }

            return $claim;
        })->toArray();
    }

    /**
     * Adds a claim to the {@see $config}.
     *
     * @param string $key
     */
    protected function addClaim(string $key, mixed $value): Builder
    {
        if (!isset($this->builder)) {
            $this->builder = $this->config->builder();
        }

        return match ($key) {
            RegisteredClaims::ID => $this->builder->identifiedBy($value),
            RegisteredClaims::EXPIRATION_TIME => $this->builder->expiresAt(DateTimeImmutable::createFromFormat('U', $value)),
            RegisteredClaims::NOT_BEFORE => $this->builder->canOnlyBeUsedAfter(DateTimeImmutable::createFromFormat('U', $value)),
            RegisteredClaims::ISSUED_AT => $this->builder->issuedAt(DateTimeImmutable::createFromFormat('U', $value)),
            RegisteredClaims::ISSUER => $this->builder->issuedBy($value),
            RegisteredClaims::AUDIENCE => $this->builder->permittedFor($value),
            RegisteredClaims::SUBJECT => $this->builder->relatedTo($value),
            default => $this->builder->withClaim($key, $value),
        };
    }

    /**
     * Get the signer instance.
     *
     * @return Signer
     *
     * @throws JWTException
     */
    protected function getSigner()
    {
        if (!array_key_exists($this->algo, $this->signers)) {
            throw new JWTException('The given algorithm could not be found');
        }

        $signer = $this->signers[$this->algo];

        return new $signer();
    }

    protected function isAsymmetric()
    {
        $reflect = new \ReflectionClass($this->signer);

        return $reflect->isSubclassOf(Rsa::class) || $reflect->isSubclassOf(Ecdsa::class);
    }

    /**
     * Get the key used to sign the tokens.
     *
     * @return Key|string
     */
    protected function getSigningKey()
    {
        return $this->isAsymmetric() ?
            InMemory::plainText($this->getPrivateKey(), $this->getPassphrase() ?? '') :
            InMemory::plainText($this->getSecret());
    }

    /**
     * Get the key used to verify the tokens.
     *
     * @return Key|string
     */
    protected function getVerificationKey()
    {
        return $this->isAsymmetric() ?
            InMemory::plainText($this->getPublicKey()) :
            InMemory::plainText($this->getSecret());
    }
}
