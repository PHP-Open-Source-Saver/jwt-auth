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

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class JWTGenerateSecretCommand extends Command
{
    use EnvHelperTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'jwt:secret
        {--s|show : Display the key instead of modifying files.}
        {--always-no : Skip generating key if it already exists.}
        {--f|force : Skip confirmation when overwriting an existing key.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the JWTAuth secret key used to sign the tokens';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $key = Str::random(64);

        if ($this->option('show')) {
            $this->comment($key);

            return;
        }

        if (!$this->envFileExists()) {
            $this->displayKey($key);

            return;
        }

        $updated = $this->updateEnvEntry('JWT_SECRET', $key, function () {
            if ($this->option('always-no')) {
                $this->comment('Secret key already exists. Skipping...');

                return false;
            }

            if (false === $this->isConfirmed()) {
                $this->comment('Phew... No changes were made to your secret key.');

                return false;
            }

            return true;
        });

        if ($updated) {
            $this->updateEnvEntry('JWT_ALGO', 'HS256');
            $this->info('jwt-auth secret set successfully.');
        }
    }

    /**
     * Display the key.
     *
     * @param string $key
     *
     * @return void
     */
    protected function displayKey($key)
    {
        $this->laravel['config']['jwt.secret'] = $key;

        $this->info("jwt-auth secret [$key] set successfully.");
    }

    /**
     * Check if the modification is confirmed.
     *
     * @return bool
     */
    protected function isConfirmed()
    {
        return $this->option('force') ? true : $this->confirm(
            'This will invalidate all existing tokens. Are you sure you want to override the secret key?'
        );
    }
}
