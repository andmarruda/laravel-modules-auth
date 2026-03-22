<?php

namespace Andmarruda\AuthModule\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateEddsaJwtKeysCommand extends Command
{
    protected $signature = 'authmodule:jwt:eddsa-keys
        {--path= : Directory where key files will be stored}
        {--env-file=.env.authmodule.jwt : File to write generated environment variables}
        {--force : Overwrite existing key/env files if they already exist}
        {--stdout : Print environment variables to stdout instead of writing env file}';

    protected $description = 'Generate EdDSA (Ed25519) JWT key pair and .env variables for AuthModule';

    public function handle(): int
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            $this->error('libsodium is required. Install/enable ext-sodium to use EdDSA JWT.');
            $this->line('Suggestion: enable ext-sodium and keep AUTHMODULE_JWT_ALGORITHM=RS256 until then.');

            return self::FAILURE;
        }

        $keysPath = $this->resolvePath((string) ($this->option('path') ?: 'storage/app/authmodule/jwt'));
        $force = (bool) $this->option('force');
        $stdout = (bool) $this->option('stdout');

        File::ensureDirectoryExists($keysPath);

        $privateKeyPath = $keysPath . DIRECTORY_SEPARATOR . 'eddsa-private.key.b64';
        $publicKeyPath = $keysPath . DIRECTORY_SEPARATOR . 'eddsa-public.key.b64';

        if (!$force && (File::exists($privateKeyPath) || File::exists($publicKeyPath))) {
            $this->error('Key files already exist. Use --force to overwrite.');

            return self::FAILURE;
        }

        $keypair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keypair);
        $publicKey = sodium_crypto_sign_publickey($keypair);

        $privateKeyBase64 = base64_encode($privateKey);
        $publicKeyBase64 = base64_encode($publicKey);

        File::put($privateKeyPath, $privateKeyBase64 . PHP_EOL);
        File::put($publicKeyPath, $publicKeyBase64 . PHP_EOL);

        $envContent = implode(PHP_EOL, [
            'AUTHMODULE_JWT_ALGORITHM=EdDSA',
            'AUTHMODULE_JWT_PRIVATE_KEY=base64:' . $privateKeyBase64,
            'AUTHMODULE_JWT_PUBLIC_KEY=base64:' . $publicKeyBase64,
            'AUTHMODULE_JWT_KEY_ID=eddsa-' . now()->format('YmdHis'),
            'AUTHMODULE_JWT_TTL_MINUTES=15',
        ]) . PHP_EOL;

        if ($stdout) {
            $this->line('');
            $this->line($envContent);
        } else {
            $envPath = $this->resolvePath((string) $this->option('env-file'));
            if (!$force && File::exists($envPath)) {
                $this->error("Env file already exists at {$envPath}. Use --force to overwrite.");

                return self::FAILURE;
            }

            File::ensureDirectoryExists(dirname($envPath));
            File::put($envPath, $envContent);
            $this->info("Environment variables written to: {$envPath}");
        }

        $this->info("Private key file: {$privateKeyPath}");
        $this->info("Public key file: {$publicKeyPath}");
        $this->line('ext-sodium is required in all environments that validate EdDSA JWT tokens.');

        return self::SUCCESS;
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return base_path();
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
