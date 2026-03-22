<?php

namespace Andmarruda\AuthModule\Tests;

use Andmarruda\AuthModule\AuthModuleServiceProvider;
use Andmarruda\AuthModule\Models\User;
use Laravel\Socialite\SocialiteServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AuthModuleServiceProvider::class,
            SocialiteServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $privateKey = <<<'PEM'
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

        $publicKey = <<<'PEM'
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

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.guards.jwt', [
            'driver' => 'jwt',
            'provider' => 'users',
        ]);
        $app['config']->set('authmodule.jwt.algorithm', 'RS256');
        $app['config']->set('authmodule.jwt.secret', '');
        $app['config']->set('authmodule.jwt.private_key', $privateKey);
        $app['config']->set('authmodule.jwt.public_key', $publicKey);
        $app['config']->set('authmodule.jwt.private_key_passphrase', '');
        $app['config']->set('authmodule.jwt.key_id', 'test-rsa-key');
        $app['config']->set('authmodule.jwt.ttl_minutes', 60);
        $app['config']->set('authmodule.jwt.issuer', 'authmodule-tests');
        $app['config']->set('authmodule.jwt.leeway_seconds', 0);
    }
}
