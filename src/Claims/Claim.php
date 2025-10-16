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

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use PHPOpenSourceSaver\JWTAuth\Contracts\Claim as ClaimContract;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;

abstract class Claim implements Arrayable, ClaimContract, Jsonable, \JsonSerializable
{
    /**
     * The claim name.
     */
    protected string $name;

    /**
     * The claim value.
     */
    private mixed $value;

    /**
     * @return void
     *
     * @throws InvalidClaimException
     */
    public function __construct(mixed $value)
    {
        $this->setValue($value);
    }

    /**
     * Set the claim value, and call a validate method.
     *
     * @throws InvalidClaimException
     */
    public function setValue(mixed $value): static
    {
        $this->value = $this->validateCreate($value);

        return $this;
    }

    /**
     * Get the claim value.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set the claim name.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the claim name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Validate the claim in a standalone Claim context.
     */
    public function validateCreate(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Validate the Claim within a Payload context.
     *
     * @return bool
     */
    public function validatePayload(): mixed
    {
        return $this->getValue();
    }

    /**
     * Validate the Claim within a refresh context.
     */
    public function validateRefresh(int $refreshTTL): bool
    {
        return $this->getValue();
    }

    /**
     * Checks if the value matches the claim.
     */
    public function matches(mixed $value, bool $strict = true): bool
    {
        return $strict ? $this->value === $value : $this->value == $value;
    }

    /**
     * Convert the object into something JSON serializable.
     */
    // @todo: what the hell is this attribute
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Build a key value array comprising of the claim name and value.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [$this->getName() => $this->getValue()];
    }

    /**
     * Get the claim as JSON.
     *
     * @param int $options
     */
    public function toJson($options = JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Get the payload as a string.
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
