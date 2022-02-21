<?php

namespace PHPOpenSourceSaver\JWTAuth\Console;

use Illuminate\Console\Command;

class JWTGenerateCertCommand extends Command
{
    use EnvHelperTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jwt:generate-certs 
        {--force : Override certificates if existing} 
        {--algo= : Algorithm (rsa/ec)} 
        {--bits= : RSA-Key length (1024,2048,4096,8192} 
        {--sha= : SHA-variant (1,224,256,384,512)} 
        {--dir= : Directory where the certificates should be placed} 
        {--curve= : EC-Curvename (e.g. secp384r1, prime256v1 )}
        {--passphrase= : Passphrase}';

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
        $force = $this->option('force');
        $directory = $this->option('dir') ? $this->option('dir') : 'storage/certs';
        $algo = $this->option('algo') ? $this->option('algo') : 'rsa';
        $bits = $this->option('bits') ? intval($this->option('bits')) : 4096;
        $shaVariant = $this->option('sha') ? intval($this->option('sha')) : 512;
        $passphrase = $this->option('passphrase') ? $this->option('passphrase') : null;
        $curve = $this->option('curve') ? $this->option('curve') : 'prime256v1';

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

        // Create the private and public key
        $res = openssl_pkey_new([
            'digest_alg' => sprintf('sha%d', $shaVariant),
            'private_key_bits' => $bits,
            'private_key_type' => $keyType,
            'curve_name' => $curve,
        ]);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey, $passphrase);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        // save certificates to disk
        if (false === is_dir($directory)) {
            mkdir($directory);
        }

        file_put_contents($filenamePrivate, $privKey);
        file_put_contents($filenamePublic, $pubKey);

        // Updated .env-file
        if (!$this->envFileExists()) {
            $this->error('.env file missing');
            return -1;
        }

        $this->updateEnvEntry('JWT_ALGO', $algoIdentifier);
        $this->updateEnvEntry('JWT_PRIVATE_KEY', sprintf("file://../%s", $filenamePrivate));
        $this->updateEnvEntry('JWT_PUBLIC_KEY', sprintf("file://../%s", $filenamePublic));
        $this->updateEnvEntry('JWT_PASSPHRASE', $passphrase ?? '');

        return 0;
    }
}
