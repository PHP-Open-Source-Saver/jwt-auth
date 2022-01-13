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

use Exception;
use InvalidArgumentException;
use Namshi\JOSE\JWS;
use Namshi\JOSE\Signer\OpenSSL\PublicKey;
use PHPOpenSourceSaver\JWTAuth\Contracts\Providers\JWT;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use ReflectionClass;
use ReflectionException;

class Namshi extends Provider implements JWT
{
    /**
     * The JWS.
     *
     * @var JWS
     */
    protected $jws;

    /**
     * Constructor.
     *
     * @param string $secret
     * @param string $algo
     *
     * @return void
     */
    public function __construct(JWS $jws, $secret, $algo, array $keys)
    {
        parent::__construct($secret, $algo, $keys);

        $this->jws = $jws;
    }

    /**
     * Create a JSON Web Token.
     *
     * @return string
     *
     * @throws JWTException
     */
    public function encode(array $payload)
    {
        try {
            $this->jws->setPayload($payload)->sign($this->getSigningKey(), $this->getPassphrase());

            return (string) $this->jws->getTokenString();
        } catch (Exception $e) {
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
            // Let's never allow insecure tokens
            $jws = $this->jws->load($token, false);
        } catch (InvalidArgumentException $e) {
            throw new TokenInvalidException('Could not decode token: '.$e->getMessage(), $e->getCode(), $e);
        }

        if (!$jws->verify($this->getVerificationKey(), $this->getAlgo())) {
            throw new TokenInvalidException('Token Signature could not be verified.');
        }

        return (array) $jws->getPayload();
    }

    /**
     * {@inheritdoc}
     */
    protected function isAsymmetric()
    {
        try {
            return (new ReflectionClass(sprintf('Namshi\\JOSE\\Signer\\OpenSSL\\%s', $this->getAlgo())))->isSubclassOf(PublicKey::class);
        } catch (ReflectionException $e) {
            throw new JWTException('The given algorithm could not be found', $e->getCode(), $e);
        }
    }
}
