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

namespace PHPOpenSourceSaver\JWTAuth\Test\Validators;

use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;
use PHPOpenSourceSaver\JWTAuth\Validators\TokenValidator;

class TokenValidatorTest extends AbstractTestCase
{
    /**
     * @var TokenValidator
     */
    protected $validator;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new TokenValidator();
    }

    /** @test */
    public function itShouldReturnTrueWhenProvidingAWellFormedToken()
    {
        $this->assertTrue($this->validator->isValid('one.two.three'));
    }

    /**
     * @test
     *
     * @dataProvider \PHPOpenSourceSaver\JWTAuth\Test\Validators\TokenValidatorTest::dataProviderMalformedTokens
     *
     * @param string $token
     */
    public function itShouldReturnFalseWhenProvidingAMalformedToken($token)
    {
        $this->assertFalse($this->validator->isValid($token));
    }

    /**
     * @test
     *
     * @dataProvider \PHPOpenSourceSaver\JWTAuth\Test\Validators\TokenValidatorTest::dataProviderMalformedTokens
     */
    public function itShouldThrowAnExceptionWhenProvidingAMalformedToken($token)
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Malformed token');

        $this->validator->check($token);
    }

    /**
     * @test
     *
     * @dataProvider \PHPOpenSourceSaver\JWTAuth\Test\Validators\TokenValidatorTest::dataProviderTokensWithWrongSegmentsNumber
     */
    public function itShouldReturnFalseWhenProvidingATokenWithWrongSegmentsNumber($token)
    {
        $this->assertFalse($this->validator->isValid($token));
    }

    /**
     * @test
     *
     * @dataProvider \PHPOpenSourceSaver\JWTAuth\Test\Validators\TokenValidatorTest::dataProviderTokensWithWrongSegmentsNumber
     */
    public function itShouldThrowAnExceptionWhenProvidingAMalformedTokenWithWrongSegmentsNumber($token)
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Wrong number of segments');

        $this->validator->check($token);
    }

    public function dataProviderMalformedTokens()
    {
        return [
            ['one.two.'],
            ['.two.'],
            ['.two.three'],
            ['one..three'],
            ['..'],
            [' . . '],
            [' one . two . three '],
        ];
    }

    public function dataProviderTokensWithWrongSegmentsNumber()
    {
        return [
            ['one.two'],
            ['one.two.three.four'],
            ['one.two.three.four.five'],
        ];
    }
}
