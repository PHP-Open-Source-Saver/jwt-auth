<?php

namespace PHPOpenSourceSaver\JWTAuth\Console;

use Closure;
use Illuminate\Support\Str;

trait EnvHelperTrait
{
    /**
     * Checks if the env file exists
     * 
     * @return bool
     */
    protected function envFileExists(): bool
    {
        return file_exists($this->envPath());
    }

    /**
     * Update an env-file entry
     * 
     * @param string $key
     * @param string|int $value
     * @param Closure|null $confirmOnExisting
     * @return bool
     */
    public function updateEnvEntry(string $key, $value, Closure $confirmOnExisting = null): bool
    {
        $filepath = $this->envPath();

        $filecontents = $this->getFileContents($filepath);

        if (false === Str::contains($filecontents, $key)) {
            // create new entry
            $this->putFileContents(
                $filepath,
                $filecontents . PHP_EOL . "{$key}={$value}" . PHP_EOL
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
     *
     * @return string
     */
    protected function envPath(): string
    {
        if (method_exists($this->laravel, 'environmentFilePath')) {
            return $this->laravel->environmentFilePath();
        }

        return $this->laravel->basePath('.env');
    }
}
