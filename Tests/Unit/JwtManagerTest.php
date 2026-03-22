<?php

namespace Andmarruda\AuthModule\Tests\Unit;

use Andmarruda\AuthModule\Infrastructure\Services\JwtManager;
use Andmarruda\AuthModule\Models\User;
use Andmarruda\AuthModule\Tests\TestCase;

class JwtManagerTest extends TestCase
{
    private const RSA_PRIVATE_KEY = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDBlup8kjNAyFAo
v+tZESgD6nOzCB3dEGNnvx3qb9yla6qgE5AtTsRL36S4qAW1MoPc5UTOLSxjLxiJ
zjJEB9uNiOlDMhSUEDeNLdHoNFK1qsQH6oJ25d9MtqskmhNzQOfEA85saI0AlcIz
z4MGGfDxe/uU7uTwSDTTWtjD38Gus0Xjl/GrpZbnlVsn9omto6X86R2euEM8x5lZ
2zmf3B7NN94mWLZeFMznRxFZKbFwwwvKNBad9wLUjUWIh+xN7/RiXTXQcByzuRsm
10Bb7eLjhENxK7PiAkg2DNKXXfR8C0TbymL2Ie5n9VXCeMLgUg5nVY1385dKegkS
ZdaG7NInAgMBAAECggEAWzLXgJv3XTuA19Gh87GrYjzfCKZ4Ox5vVf6o/zp3fc58
8TCGKXID86KGHR+6oKQNjtDLWz3YvVkAfFsRPw9clQ68pRVAsIf7Q50kV/neRQsP
kpMdpxmf2qnrcjDLnO4kwSjx5AxU8MOcW4AUv5kU8w5vdLgeTjwV9sPDfnM48S1d
EYEEDCnWqI9ybTkqEaabNOoVRQjllWynMJpBAbyptLUmFOPFcXxqY5D6QFyH9srg
XHE8EQ5uERJ/9169UIIaupL6W7mFYNBWnl4lfQrYNhVmHZkE3kRR6lEW+3HBGrl9
xJU89pG9QsQXXjyEhGaLyfxwN49/LvW+p+ncuST7AQKBgQDwOYylUl6RqT3tG3Y0
ilF3/P2p5qEC+a0SLP44Lb0vY/08pPAAgv0LPHi9bjsRptcXwYrYx9Gi/9M3fAeG
BnJxgE6i6PEJDqNM8OJM4HWG5L8o7lRzJfOoHZhqrw04IxxZNr4JZU6+2hJ44vFj
sXSBEFSdkDJKibNdhiQV1G3fQQKBgQDOTV/pQJohXaq8w+snKWKOnowP4IDmSzYE
uAD2RMBDsZvrgDuZtAGvU3L8uhqNHw35HTHiuWLS14ZSfXq0qMYriC02ZUAVVWdV
ub1lv+Qb16cRsm0ZCh9fjBEXybqu8aVmz1xvfaHI6iERBpE/1SPD5TBhhOLs0EHj
keQVagc/ZwKBgGBsmTna4D7TkhnUhhP82UqycBd8jXCmS4QaL0jZgzC8j++BvRxX
d77E3SocTvV85KWSeGsfedRVn7CLxnFTsShB+k6F9gpOp9nAbvWwuzwUIW8KizdV
KoJ2rrFT5ph772sYABvBYGRXIEcJwR7lIgCUT4KXWLh2oVqO93kTta6BAoGADlEI
SuOUzqP1aUwfUYRptoQCLEfkKhcmFIXAa1ayEQCOVXV8rVn0k/oyjJ9NoGV7TsJb
5+P9m6whQjA7B7Z+qh61JIU6QUC//w74ucrrRZNdoLjFIWu2aacKuJ/jOKjEVbIm
NAZ/57V3vfq6sbdU2A9boCjcppp9OBGzxlipUBkCgYBgn5jgEpBAntzZuDks+8rv
FvleOLNfp2a2dba/eE4v47W6FawvzPrQufPnK+xvhj0FEmlTjs1OrNASVFyZP9G8
Baib7xdygHI10iOkthFhcOuTfLEjmuGloCcYDrf4ATHHtSRy3Jx/L87gbJAm28CG
kRt0WunjwtTehevpo6TzWg==
-----END PRIVATE KEY-----
PEM;

    private const RSA_PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwZbqfJIzQMhQKL/rWREo
A+pzswgd3RBjZ78d6m/cpWuqoBOQLU7ES9+kuKgFtTKD3OVEzi0sYy8Yic4yRAfb
jYjpQzIUlBA3jS3R6DRStarEB+qCduXfTLarJJoTc0DnxAPObGiNAJXCM8+DBhnw
8Xv7lO7k8Eg001rYw9/BrrNF45fxq6WW55VbJ/aJraOl/OkdnrhDPMeZWds5n9we
zTfeJli2XhTM50cRWSmxcMMLyjQWnfcC1I1FiIfsTe/0Yl010HAcs7kbJtdAW+3i
44RDcSuz4gJINgzSl130fAtE28pi9iHuZ/VVwnjC4FIOZ1WNd/OXSnoJEmXWhuzS
JwIDAQAB
-----END PUBLIC KEY-----
PEM;

    public function test_rs256_can_issue_and_decode_token(): void
    {
        $manager = new JwtManager(
            algorithm: 'RS256',
            secret: '',
            privateKey: self::RSA_PRIVATE_KEY,
            publicKey: self::RSA_PUBLIC_KEY,
            privateKeyPassphrase: null,
            keyId: 'unit-rsa',
            ttlMinutes: 10,
            issuer: 'tests',
            leewaySeconds: 0,
        );

        $user = new User();
        $user->id = 123;

        $token = $manager->issueToken($user);
        $payload = $manager->decode($token);

        $this->assertIsArray($payload);
        $this->assertSame('123', $payload['sub'] ?? null);
        $this->assertSame('tests', $payload['iss'] ?? null);
    }

    public function test_rs256_rejects_tampered_token(): void
    {
        $manager = new JwtManager(
            algorithm: 'RS256',
            secret: '',
            privateKey: self::RSA_PRIVATE_KEY,
            publicKey: self::RSA_PUBLIC_KEY,
            privateKeyPassphrase: null,
            keyId: 'unit-rsa',
            ttlMinutes: 10,
            issuer: 'tests',
            leewaySeconds: 0,
        );

        $user = new User();
        $user->id = 321;

        $token = $manager->issueToken($user);
        $parts = explode('.', $token);
        $parts[1] = rtrim(strtr(base64_encode('{"sub":"999"}'), '+/', '-_'), '=');
        $tampered = implode('.', $parts);

        $this->assertNull($manager->decode($tampered));
    }

    public function test_eddsa_can_issue_and_decode_token_when_sodium_is_available(): void
    {
        if (!function_exists('sodium_crypto_sign_keypair')) {
            $this->markTestSkipped('libsodium is not available.');
        }

        $keypair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keypair);
        $publicKey = sodium_crypto_sign_publickey($keypair);

        $manager = new JwtManager(
            algorithm: 'EdDSA',
            secret: '',
            privateKey: $privateKey,
            publicKey: $publicKey,
            privateKeyPassphrase: null,
            keyId: 'unit-ed25519',
            ttlMinutes: 10,
            issuer: 'tests',
            leewaySeconds: 0,
        );

        $user = new User();
        $user->id = 999;

        $token = $manager->issueToken($user);
        $payload = $manager->decode($token);

        $this->assertIsArray($payload);
        $this->assertSame('999', $payload['sub'] ?? null);
    }
}
