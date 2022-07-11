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

namespace PHPOpenSourceSaver\JWTAuth\Test\Console;

use PHPOpenSourceSaver\JWTAuth\Console\EnvHelperTrait;
use PHPOpenSourceSaver\JWTAuth\Test\AbstractTestCase;

class MockEnvHelperClass {
    use EnvHelperTrait;

    function envFileExists(): bool {
        return true;
    }

    protected string $dummy = '';

    public function getFileContents(string $filepath): string
    {
        return $this->dummy;
    }

    public function putFileContents(string $filepath, string $data): void
    {
        $this->dummy = $data;
    }

    protected function envPath(): string
    {
        return 'N/A';
    }
}

class EnvHelperTraitTest extends AbstractTestCase
{
    public function testEmptyEnv() {
        $sut = new MockEnvHelperClass();

        $this->assertEmpty($sut->getFileContents('.env'));

        $sut->updateEnvEntry('JWT_TEST', '123');

        $this->assertEquals("\nJWT_TEST=123\n", $sut->getFileContents('.env'));
    }

    public function testUpdateEnv() {
        $sut = new MockEnvHelperClass();

        $sut->putFileContents('.env', "\nJWT_TEST=123\n");

        $this->assertEquals("\nJWT_TEST=123\n", $sut->getFileContents('.env'));

        $sut->updateEnvEntry('JWT_TEST', '456');

        $this->assertEquals("\nJWT_TEST=456\n", $sut->getFileContents('.env'));
    }
}