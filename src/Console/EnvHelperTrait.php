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

namespace PHPOpenSourceSaver\JWTAuth\Console;

use Illuminate\Support\Str;

trait EnvHelperTrait
{
    /**
     * Checks if the env file exists.
     */
    protected function envFileExists(): bool
    {
        return file_exists($this->envPath());
    }

    /**
     * Update an env-file entry.
     *
     * @param string|int $value
     */
    public function updateEnvEntry(string $key, $value, ?\Closure $confirmOnExisting = null): bool
    {
        $filepath = $this->envPath();

        $filecontents = $this->getFileContents($filepath);

        if (false === Str::contains($filecontents, $key)) {
            // create new entry
            $this->putFileContents(
                $filepath,
                $filecontents.PHP_EOL."{$key}={$value}".PHP_EOL
            );

            return true;
        } else {
            if (is_null($confirmOnExisting) || $confirmOnExisting()) {
                // update existing entry
                $this->putFileContents(
                    $filepath,
                    preg_replace(
                        "/{$key}=.*/",
                        "{$key}={$value}",
                        $filecontents
                    )
                );

                return true;
            }
        }

        return false;
    }

    protected function getFileContents(string $filepath): string
    {
        return file_get_contents($filepath);
    }

    protected function putFileContents(string $filepath, string $data): void
    {
        file_put_contents($filepath, $data);
    }

    /**
     * Get the .env file path.
     */
    protected function envPath(): string
    {
        if (method_exists($this->laravel, 'environmentFilePath')) {
            return $this->laravel->environmentFilePath();
        }

        return $this->laravel->basePath('.env');
    }
}
