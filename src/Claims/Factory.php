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

namespace PHPOpenSourceSaver\JWTAuth\Claims;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Support\Utils;

class Factory
{
    /**
     * The Laravel request.
     */
    protected Request $request;

    /**
     * The time to live in minutes.
     */
    protected int $ttl = 60;

    /**
     * Time leeway in seconds.
     */
    protected int $leeway = 0;

    /**
     * The classes map.
     *
     * @var array
     */
    private array $classMap = [
        'aud' => Audience::class,
        'exp' => Expiration::class,
        'iat' => IssuedAt::class,
        'iss' => Issuer::class,
        'jti' => JwtId::class,
        'nbf' => NotBefore::class,
        'sub' => Subject::class,
    ];

    /**
     * Constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the instance of the claim when passing the name and value.
     *
     * @throws InvalidClaimException
     */
    public function get(string $name, mixed $value): Custom
    {
        if ($this->has($name)) {
            $claim = new $this->classMap[$name]($value);

            return method_exists($claim, 'setLeeway') ?
                $claim->setLeeway($this->leeway) :
                $claim;
        }

        return new Custom($name, $value);
    }

    /**
     * Check whether the claim exists.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->classMap);
    }

    /**
     * Generate the initial value and return the Claim instance.
     *
     * @throws InvalidClaimException
     */
    public function make(string $name): Claim
    {
        return $this->get($name, $this->$name());
    }

    /**
     * Get the Issuer (iss) claim.
     */
    public function iss(): string
    {
        return $this->request->url() ?? '';
    }

    /**
     * Get the Issued At (iat) claim.
     */
    public function iat(): int
    {
        return Utils::now()->getTimestamp();
    }

    /**
     * Get the Expiration (exp) claim as a unix timestamp
     */
    public function exp(): int
    {
        return Utils::now()->addMinutes($this->ttl)->getTimestamp();
    }

    /**
     * Get the Not Before (nbf) claim as a unix timestamp
     */
    public function nbf(): int
    {
        return Utils::now()->getTimestamp();
    }

    /**
     * Get the JWT Id (jti) claim.
     */
    public function jti(): string
    {
        return Str::random();
    }

    /**
     * Add a new claim mapping.
     */
    public function extend(string $name, string $classPath): self
    {
        $this->classMap[$name] = $classPath;

        return $this;
    }

    /**
     * Set the Laravel request instance.
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Set the token ttl (in minutes).
     */
    public function setTTL(int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * Get the token ttl.
     */
    public function getTTL(): int
    {
        return $this->ttl;
    }

    /**
     * Set the leeway in seconds.
     */
    public function setLeeway(int $leeway): self
    {
        $this->leeway = $leeway;

        return $this;
    }
}
