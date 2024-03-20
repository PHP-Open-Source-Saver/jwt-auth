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

namespace PHPOpenSourceSaver\JWTAuth\Test;

use Mockery\LegacyMockInterface;
use PHPOpenSourceSaver\JWTAuth\Claims\Audience;
use PHPOpenSourceSaver\JWTAuth\Claims\Claim;
use PHPOpenSourceSaver\JWTAuth\Claims\Collection;
use PHPOpenSourceSaver\JWTAuth\Claims\Expiration;
use PHPOpenSourceSaver\JWTAuth\Claims\IssuedAt;
use PHPOpenSourceSaver\JWTAuth\Claims\Issuer;
use PHPOpenSourceSaver\JWTAuth\Claims\JwtId;
use PHPOpenSourceSaver\JWTAuth\Claims\NotBefore;
use PHPOpenSourceSaver\JWTAuth\Claims\Subject;
use PHPOpenSourceSaver\JWTAuth\Exceptions\InvalidClaimException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\PayloadException;
use PHPOpenSourceSaver\JWTAuth\Payload;
use PHPOpenSourceSaver\JWTAuth\Validators\PayloadValidator;

class PayloadTest extends AbstractTestCase
{
    protected ?LegacyMockInterface $validator = null;

    /**
     * @var Payload
     */
    protected $payload;

    public function setUp(): void
    {
        parent::setUp();

        $this->payload = $this->getTestPayload();
    }

    /**
     * @throws InvalidClaimException
     */
    private function getTestPayload(array $extraClaims = []): Payload
    {
        $claims = [
            new Subject(1),
            new Issuer('http://example.com'),
            new Expiration($this->testNowTimestamp + 3600),
            new NotBefore($this->testNowTimestamp),
            new IssuedAt($this->testNowTimestamp),
            new JwtId('foo'),
        ];

        if ($extraClaims) {
            $claims = array_merge($claims, $extraClaims);
        }

        $collection = Collection::make($claims);

        $this->validator = \Mockery::mock(PayloadValidator::class);
        $this->validator->shouldReceive('setRefreshFlow->check')->andReturn($collection);

        return new Payload($collection, $this->validator);
    }

    public function testItShouldThrowAnExceptionWhenTryingToAddToThePayload()
    {
        $this->expectException(PayloadException::class);
        $this->expectExceptionMessage('The payload is immutable');

        $this->payload['foo'] = 'bar';
    }

    public function testItShouldThrowAnExceptionWhenTryingToRemoveAKeyFromThePayload()
    {
        $this->expectException(PayloadException::class);
        $this->expectExceptionMessage('The payload is immutable');

        unset($this->payload['foo']);
    }

    public function testItShouldCastThePayloadToAStringAsJson()
    {
        $this->assertSame((string) $this->payload, json_encode($this->payload->get(), JSON_UNESCAPED_SLASHES));
        $this->assertJsonStringEqualsJsonString((string) $this->payload, json_encode($this->payload->get()));
    }

    public function testItShouldAllowArrayAccessOnThePayload()
    {
        $this->assertTrue(isset($this->payload['iat']));
        $this->assertSame($this->payload['sub'], 1);
        $this->assertArrayHasKey('exp', $this->payload);
    }

    public function testItShouldGetPropertiesOfPayloadViaGetMethod()
    {
        $this->assertIsArray($this->payload->get());
        $this->assertSame($this->payload->get('sub'), 1);

        $this->assertSame(
            $this->payload->get(fn () => 'jti'),
            'foo'
        );
    }

    public function testItShouldGetMultiplePropertiesWhenPassingAnArrayToTheGetMethod()
    {
        $values = $this->payload->get(['sub', 'jti']);

        $sub = $values[0];
        $jti = $values[1];

        $this->assertIsArray($values);
        $this->assertSame($sub, 1);
        $this->assertSame($jti, 'foo');
    }

    public function testItShouldDetermineWhetherThePayloadHasAClaim()
    {
        $this->assertTrue($this->payload->has(new Subject(1)));
        $this->assertFalse($this->payload->has(new Audience(1)));
    }

    public function testItShouldMagicallyGetAProperty()
    {
        $sub = $this->payload->getSubject();
        $jti = $this->payload->getJwtId();
        $iss = $this->payload->getIssuer();

        $this->assertSame($sub, 1);
        $this->assertSame($jti, 'foo');
        $this->assertSame($iss, 'http://example.com');
    }

    public function testItShouldInvokeTheInstanceAsACallable()
    {
        $payload = $this->payload;

        $sub = $payload('sub');
        $jti = $payload('jti');
        $iss = $payload('iss');

        $this->assertSame($sub, 1);
        $this->assertSame($jti, 'foo');
        $this->assertSame($iss, 'http://example.com');

        $this->assertSame($payload(), $this->payload->toArray());
    }

    public function testItShouldThrowAnExceptionWhenMagicallyGettingAPropertyThatDoesNotExist()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('The claim [Foo] does not exist on the payload');

        $this->payload->getFoo();
    }

    public function testItShouldGetTheClaims()
    {
        $claims = $this->payload->getClaims();

        $this->assertInstanceOf(Expiration::class, $claims['exp']);
        $this->assertInstanceOf(JwtId::class, $claims['jti']);
        $this->assertInstanceOf(Subject::class, $claims['sub']);

        $this->assertContainsOnlyInstancesOf(Claim::class, $claims);
    }

    public function testItShouldGetTheObjectAsJson()
    {
        $this->assertJsonStringEqualsJsonString(json_encode($this->payload), $this->payload->toJson());
    }

    public function testItShouldCountTheClaims()
    {
        $this->assertSame(6, $this->payload->count());
        $this->assertCount(6, $this->payload);
    }

    public function testItShouldMatchValues()
    {
        $values = $this->payload->toArray();
        $values['sub'] = (string) $values['sub'];

        $this->assertTrue($this->payload->matches($values));
    }

    public function testItShouldMatchStrictValues()
    {
        $values = $this->payload->toArray();

        $this->assertTrue($this->payload->matchesStrict($values));
        $this->assertTrue($this->payload->matches($values, true));
    }

    public function testItShouldNotMatchEmptyValues()
    {
        $this->assertFalse($this->payload->matches([]));
    }

    public function testItShouldNotMatchValues()
    {
        $values = $this->payload->toArray();
        $values['sub'] = 'dummy_subject';

        $this->assertFalse($this->payload->matches($values));
    }

    public function testItShouldNotMatchStrictValues()
    {
        $values = $this->payload->toArray();
        $values['sub'] = (string) $values['sub'];

        $this->assertFalse($this->payload->matchesStrict($values));
        $this->assertFalse($this->payload->matches($values, true));
    }

    public function testItShouldNotMatchANonExistingClaim()
    {
        $values = ['foo' => 'bar'];

        $this->assertFalse($this->payload->matches($values));
    }
}
