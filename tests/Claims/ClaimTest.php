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

namespace PHPOpenSourceSaver\JWTAuth\Test\Claims;

use Illuminate\Contracts\Support\Arrayable;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;

class ClaimTest extends AbstractTestCase
{
    protected Expiration $claim;

    /**
     * @throws InvalidClaimException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->claim = new Expiration($this->testNowTimestamp);
    }

    public function testItShouldThrowAnExceptionWhenPassingAnInvalidValue()
    {
        $this->expectException(InvalidClaimException::class);
        $this->expectExceptionMessage('Invalid value provided for claim [exp]');

        $this->claim->setValue('foo');
    }

    public function testItShouldConvertTheClaimToAnArray()
    {
        $this->assertSame(['exp' => $this->testNowTimestamp], $this->claim->toArray());
    }

    public function testItShouldGetTheClaimAsAString()
    {
        $this->assertJsonStringEqualsJsonString((string) $this->claim, $this->claim->toJson());
    }

    public function testItShouldGetTheObjectAsJson()
    {
        $this->assertJsonStringEqualsJsonString(json_encode($this->claim), $this->claim->toJson());
    }

    public function testItShouldImplementArrayable()
    {
        $this->assertInstanceOf(Arrayable::class, $this->claim);
    }
}
