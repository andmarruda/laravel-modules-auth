<?php

namespace Andmarruda\AuthModule\Infrastructure\Services;

use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Ports\Services\JwtManagerInterface;
use JsonException;
use RuntimeException;

class JwtManager implements JwtManagerInterface
{
    private const ALG_HS256 = 'HS256';
    private const ALG_RS256 = 'RS256';
    private const ALG_EDDSA = 'EdDSA';

    private string $algorithm;
    private string $secret;
    private string $privateKey;
    private string $publicKey;
    private ?string $privateKeyPassphrase;
    private ?string $keyId;

    public function __construct(
        string $algorithm,
        string $secret,
        string $privateKey,
        string $publicKey,
        ?string $privateKeyPassphrase,
        ?string $keyId,
        private int $ttlMinutes,
        private string $issuer,
        private int $leewaySeconds = 0,
    ) {
        $this->algorithm = $this->normalizeAlgorithm($algorithm);
        $this->secret = $this->normalizeSecret($secret);
        $this->privateKey = $this->normalizeKeyMaterial($privateKey);
        $this->publicKey = $this->normalizeKeyMaterial($publicKey);
        $this->privateKeyPassphrase = $privateKeyPassphrase !== null && $privateKeyPassphrase !== ''
            ? $privateKeyPassphrase
            : null;
        $this->keyId = $keyId !== null && $keyId !== '' ? $keyId : null;
    }

    public function issueToken(User $user, array $customClaims = []): string
    {
        $now = time();
        $ttlSeconds = max(1, $this->ttlMinutes) * 60;

        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT',
        ];
        if ($this->keyId !== null) {
            $header['kid'] = $this->keyId;
        }

        $payload = array_merge([
            'iss' => $this->issuer,
            'sub' => (string) $user->getAuthIdentifier(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttlSeconds,
        ], $customClaims);

        try {
            $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
            $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode JWT payload.', previous: $exception);
        }

        $signature = $this->signRaw("{$encodedHeader}.{$encodedPayload}");

        return "{$encodedHeader}.{$encodedPayload}." . $this->base64UrlEncode($signature);
    }

    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        if ($encodedHeader === '' || $encodedPayload === '' || $encodedSignature === '') {
            return null;
        }

        try {
            $header = $this->decodeJson($encodedHeader);
            $payload = $this->decodeJson($encodedPayload);
        } catch (JsonException) {
            return null;
        }

        if (($header['alg'] ?? null) !== $this->algorithm) {
            return null;
        }

        $signature = $this->base64UrlDecode($encodedSignature);
        if ($signature === null) {
            return null;
        }

        if (!$this->verifyRaw("{$encodedHeader}.{$encodedPayload}", $signature)) {
            return null;
        }

        if (!$this->passesTimeValidation($payload)) {
            return null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $segment): array
    {
        $decoded = $this->base64UrlDecode($segment);
        if ($decoded === null) {
            throw new JsonException('Invalid JWT segment.');
        }

        /** @var array<string, mixed> $json */
        $json = json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);

        return $json;
    }

    private function passesTimeValidation(array $payload): bool
    {
        $now = time();
        $leeway = max(0, $this->leewaySeconds);

        if (isset($payload['nbf']) && is_numeric($payload['nbf']) && $now + $leeway < (int) $payload['nbf']) {
            return false;
        }

        if (isset($payload['iat']) && is_numeric($payload['iat']) && $now + $leeway < (int) $payload['iat']) {
            return false;
        }

        if (!isset($payload['exp']) || !is_numeric($payload['exp'])) {
            return false;
        }

        return $now - $leeway < (int) $payload['exp'];
    }

    private function signRaw(string $input): string
    {
        return match ($this->algorithm) {
            self::ALG_HS256 => $this->signHs256($input),
            self::ALG_RS256 => $this->signRs256($input),
            self::ALG_EDDSA => $this->signEddsa($input),
            default => throw new RuntimeException('Unsupported JWT algorithm.'),
        };
    }

    private function verifyRaw(string $input, string $signature): bool
    {
        return match ($this->algorithm) {
            self::ALG_HS256 => hash_equals($this->signHs256($input), $signature),
            self::ALG_RS256 => $this->verifyRs256($input, $signature),
            self::ALG_EDDSA => $this->verifyEddsa($input, $signature),
            default => false,
        };
    }

    private function signHs256(string $input): string
    {
        if ($this->secret === '') {
            throw new RuntimeException('JWT secret is not configured for HS256.');
        }

        return hash_hmac('sha256', $input, $this->secret, true);
    }

    private function signRs256(string $input): string
    {
        $privateKey = $this->resolveOpenSslPrivateKey();

        $signature = '';
        $signed = openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            throw new RuntimeException('Unable to sign JWT using RS256.');
        }

        return $signature;
    }

    private function verifyRs256(string $input, string $signature): bool
    {
        $publicKey = $this->resolveOpenSslPublicKey();

        return openssl_verify($input, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    private function signEddsa(string $input): string
    {
        if (!function_exists('sodium_crypto_sign_detached')) {
            throw new RuntimeException('libsodium extension is required for EdDSA.');
        }

        $privateKey = $this->resolveEddsaPrivateKey();

        return sodium_crypto_sign_detached($input, $privateKey);
    }

    private function verifyEddsa(string $input, string $signature): bool
    {
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $publicKey = $this->resolveEddsaPublicKey();

        return sodium_crypto_sign_verify_detached($signature, $input, $publicKey);
    }

    /**
     * @return \OpenSSLAsymmetricKey|resource
     */
    private function resolveOpenSslPrivateKey()
    {
        if ($this->privateKey === '') {
            throw new RuntimeException('JWT private key is not configured for RS256.');
        }

        $privateKey = openssl_pkey_get_private($this->privateKey, $this->privateKeyPassphrase ?? '');
        if ($privateKey === false) {
            throw new RuntimeException('Invalid JWT private key for RS256.');
        }

        return $privateKey;
    }

    /**
     * @return \OpenSSLAsymmetricKey|resource
     */
    private function resolveOpenSslPublicKey()
    {
        if ($this->publicKey === '') {
            throw new RuntimeException('JWT public key is not configured for RS256.');
        }

        $publicKey = openssl_pkey_get_public($this->publicKey);
        if ($publicKey === false) {
            throw new RuntimeException('Invalid JWT public key for RS256.');
        }

        return $publicKey;
    }

    private function resolveEddsaPrivateKey(): string
    {
        if ($this->privateKey === '') {
            throw new RuntimeException('JWT private key is not configured for EdDSA.');
        }

        if (strlen($this->privateKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('Invalid EdDSA private key length.');
        }

        return $this->privateKey;
    }

    private function resolveEddsaPublicKey(): string
    {
        if ($this->publicKey === '') {
            throw new RuntimeException('JWT public key is not configured for EdDSA.');
        }

        if (strlen($this->publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('Invalid EdDSA public key length.');
        }

        return $this->publicKey;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }

    private function normalizeSecret(string $secret): string
    {
        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if (is_string($decoded)) {
                return $decoded;
            }
        }

        return $secret;
    }

    private function normalizeAlgorithm(string $algorithm): string
    {
        $normalized = strtoupper(trim($algorithm));

        return match ($normalized) {
            'HS256' => self::ALG_HS256,
            'RS256' => self::ALG_RS256,
            'EDDSA' => self::ALG_EDDSA,
            default => throw new RuntimeException("Unsupported JWT algorithm: {$algorithm}."),
        };
    }

    private function normalizeKeyMaterial(string $key): string
    {
        if ($key === '') {
            return '';
        }

        $normalized = str_replace('\\n', "\n", $key);

        if (str_starts_with($normalized, 'base64:')) {
            $decoded = base64_decode(substr($normalized, 7), true);
            if (is_string($decoded)) {
                return $decoded;
            }
        }

        return $normalized;
    }
}
