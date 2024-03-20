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
use PHPUnit\Framework\Attributes\DataProviderExternal;

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

    public function testItShouldReturnTrueWhenProvidingAWellFormedToken()
    {
        $this->assertTrue($this->validator->isValid('one.two.three'));
    }

    #[DataProviderExternal(TokenValidatorTest::class, 'dataProviderMalformedTokens')]
    public function testItShouldReturnFalseWhenProvidingAMalformedToken(string $token)
    {
        $this->assertFalse($this->validator->isValid($token));
    }

    #[DataProviderExternal(TokenValidatorTest::class, 'dataProviderMalformedTokens')]
    public function testItShouldThrowAnExceptionWhenProvidingAMalformedToken($token)
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Malformed token');

        $this->validator->check($token);
    }

    #[DataProviderExternal(TokenValidatorTest::class, 'dataProviderTokensWithWrongSegmentsNumber')]
    public function testItShouldReturnFalseWhenProvidingATokenWithWrongSegmentsNumber($token)
    {
        $this->assertFalse($this->validator->isValid($token));
    }

    #[DataProviderExternal(TokenValidatorTest::class, 'dataProviderTokensWithWrongSegmentsNumber')]
    public function testItShouldThrowAnExceptionWhenProvidingAMalformedTokenWithWrongSegmentsNumber($token)
    {
        $this->expectException(TokenInvalidException::class);
        $this->expectExceptionMessage('Wrong number of segments');

        $this->validator->check($token);
    }

    public static function dataProviderMalformedTokens()
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

    public static function dataProviderTokensWithWrongSegmentsNumber()
    {
        return [
            ['one.two'],
            ['one.two.three.four'],
            ['one.two.three.four.five'],
        ];
    }
}
