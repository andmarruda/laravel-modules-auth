<?php

namespace Andmarruda\AuthModule\Tests\Feature;

use Andmarruda\AuthModule\Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateEddsaJwtKeysCommandTest extends TestCase
{
    public function test_command_generates_eddsa_keys_and_env_file(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('libsodium is not available.');
        }

        $baseDir = '/tmp/authmodule-jwt-command-test-' . bin2hex(random_bytes(6));
        $keysDir = $baseDir . '/keys';
        $envFile = $baseDir . '/generated.env';

        try {
            $this->artisan('authmodule:jwt:eddsa-keys', [
                '--path' => $keysDir,
                '--env-file' => $envFile,
                '--force' => true,
            ])->assertExitCode(0);

            $this->assertFileExists($keysDir . '/eddsa-private.key.b64');
            $this->assertFileExists($keysDir . '/eddsa-public.key.b64');
            $this->assertFileExists($envFile);

            $envContent = File::get($envFile);
            $this->assertStringContainsString('AUTHMODULE_JWT_ALGORITHM=EdDSA', $envContent);
            $this->assertStringContainsString('AUTHMODULE_JWT_PRIVATE_KEY=base64:', $envContent);
            $this->assertStringContainsString('AUTHMODULE_JWT_PUBLIC_KEY=base64:', $envContent);
        } finally {
            File::deleteDirectory($baseDir);
        }
    }
}
