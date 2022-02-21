<?php

namespace PHPOpenSourceSaver\JWTAuth\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class JWTGenerateCertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:generate-certs {--force : Override certificates if existing} {--algo : Algorithm} {--bits : Key length} {--sha : SHA-variant} {--dir : Directory} {--passphrase : Passphrase}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a new cert pair';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $force = boolval($this->option('force')) ?? false;
        $directory = $this->option('dir') ?? 'storage/certs';
        $algo = $this->option('algo') ?? 'rsa';
        $bits = intval($this->option('bits')) ?? 4096;
        $shaVariant = intval($this->option('sha')) ?? 512;
        $passphrase = $this->option('passphrase');

        $filenamePublic = sprintf('%s/jwt-%s-%d-public.pem', $directory, $algo, $bits);
        $filenamePrivate = sprintf('%s/jwt-%s-%d-private.pem', $directory, $algo, $bits);

        if (true === file_exists($filenamePrivate)) {
            $this->warn('Private cert already exists');

            if (!$force) {
                $this->warn('Aborting');
                return;
            }
        }

        if (true === file_exists($filenamePublic)) {
            $this->warn('Public cert already exists');

            if (!$force) {
                $this->warn('Aborting');
                return;
            }
        }

        switch ($algo) {
            case 'rsa': {
                    $keyType = OPENSSL_KEYTYPE_RSA;
                    $algoIdentifier = sprintf('RS%d', $shaVariant);
                    break;
                }
            case 'ec': {
                    $keyType = OPENSSL_KEYTYPE_EC;
                    $algoIdentifier = sprintf('ES%d', $shaVariant);
                    break;
                }
            default: {
                    $this->error('Unknown algorithm');
                    return -1;
                }
        }

        $config = array(
            "digest_alg" => sprintf('sha%d', $shaVariant),
            "private_key_bits" => $bits,
            "private_key_type" => $keyType,
        );

        // Create the private and public key
        $res = openssl_pkey_new($config);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey, $passphrase);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        file_put_contents($filenamePrivate, $privKey);
        file_put_contents($filenamePublic, $pubKey);

        if (false === file_exists($envFilePath = $this->envPath())) {
            $this->error('.env file missing');
            return -1;
        }

        $this->updateEnvEntry($envFilePath, 'JWT_ALGO', $algoIdentifier);
        $this->updateEnvEntry($envFilePath, 'JWT_PRIVATE_KEY', sprintf("file://../%s", $filenamePrivate));
        $this->updateEnvEntry($envFilePath, 'JWT_PUBLIC_KEY', sprintf("file://../%s", $filenamePublic));
        $this->updateEnvEntry($envFilePath, 'JWT_PASSPHRASE', sprintf("file://../%s", $passphrase ?? ''));
    }

    function updateEnvEntry(string $filepath, string $key, $value)
    {
        if (false === Str::contains(file_get_contents($filepath), $key)) {
            // create new entry
            file_put_contents(
                $filepath,
                PHP_EOL . "{$key}={$value}" . PHP_EOL,
                FILE_APPEND
            );
        } else {
            file_put_contents(
                $filepath,
                str_replace(
                    "/{$key}=.*/",
                    "{$key}={$value}",
                    file_get_contents($filepath)
                )
            );
        }
    }
}
