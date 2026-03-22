<?php

namespace Andmarruda\AuthModule;

use Andmarruda\AuthModule\Infrastructure\Console\Commands\GenerateEddsaJwtKeysCommand;
use Andmarruda\AuthModule\Infrastructure\Services\JwtManager;
use Andmarruda\AuthModule\Infrastructure\Persistence\EloquentInvitationRepository;
use Andmarruda\AuthModule\Infrastructure\Persistence\EloquentOtpRepository;
use Andmarruda\AuthModule\Infrastructure\Persistence\EloquentUserRepository;
use Andmarruda\AuthModule\Infrastructure\Services\EloquentAuditLogger;
use Andmarruda\AuthModule\Infrastructure\Services\MailInvitationMailer;
use Andmarruda\AuthModule\Infrastructure\Services\MailOtpMailer;
use Andmarruda\AuthModule\Infrastructure\Services\SecureTokenGenerator;
use Andmarruda\AuthModule\Http\Middleware\EnsureSocialProfileIsComplete;
use Andmarruda\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use Andmarruda\AuthModule\Ports\Repositories\OtpRepositoryInterface;
use Andmarruda\AuthModule\Ports\Repositories\UserRepositoryInterface;
use Andmarruda\AuthModule\Ports\Services\AuditLoggerInterface;
use Andmarruda\AuthModule\Ports\Services\InvitationMailerInterface;
use Andmarruda\AuthModule\Ports\Services\JwtManagerInterface;
use Andmarruda\AuthModule\Ports\Services\OtpMailerInterface;
use Andmarruda\AuthModule\Ports\Services\TokenGeneratorInterface;
use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AuthModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        InvitationRepositoryInterface::class => EloquentInvitationRepository::class,
        OtpRepositoryInterface::class => EloquentOtpRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
        AuditLoggerInterface::class => EloquentAuditLogger::class,
        InvitationMailerInterface::class => MailInvitationMailer::class,
        OtpMailerInterface::class => MailOtpMailer::class,
        TokenGeneratorInterface::class => SecureTokenGenerator::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/authmodule.php', 'authmodule');

        $this->app->bind(JwtManagerInterface::class, static function (): JwtManagerInterface {
            return new JwtManager(
                algorithm: (string) config('authmodule.jwt.algorithm', 'RS256'),
                secret: (string) config('authmodule.jwt.secret', ''),
                privateKey: (string) config('authmodule.jwt.private_key', ''),
                publicKey: (string) config('authmodule.jwt.public_key', ''),
                privateKeyPassphrase: config('authmodule.jwt.private_key_passphrase'),
                keyId: config('authmodule.jwt.key_id'),
                ttlMinutes: (int) config('authmodule.jwt.ttl_minutes', 60),
                issuer: (string) config('authmodule.jwt.issuer', 'authmodule'),
                leewaySeconds: (int) config('authmodule.jwt.leeway_seconds', 0),
            );
        });
    }

    public function boot(): void
    {
        $this->registerJwtGuard();

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateEddsaJwtKeysCommand::class,
            ]);
        }

        $this->app->make(Router::class)->aliasMiddleware(
            'authmodule.profile.complete',
            EnsureSocialProfileIsComplete::class,
        );

        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'authmodule');
        $this->publishes([
            __DIR__ . '/Config/authmodule.php' => config_path('authmodule.php'),
        ], 'authmodule-config');
    }

    private function registerJwtGuard(): void
    {
        Auth::extend('jwt', function ($app, string $name, array $config): RequestGuard {
            $provider = $app['auth']->createUserProvider($config['provider'] ?? null);
            $jwtManager = $app->make(JwtManagerInterface::class);

            $guard = new RequestGuard(
                static function (Request $request) use ($provider, $jwtManager) {
                    if ($provider === null) {
                        return null;
                    }

                    $token = $request->bearerToken();
                    if (!is_string($token) || $token === '') {
                        return null;
                    }

                    $payload = $jwtManager->decode($token);
                    if (!is_array($payload)) {
                        return null;
                    }

                    $subject = $payload['sub'] ?? null;
                    if (!is_string($subject) && !is_int($subject)) {
                        return null;
                    }

                    return $provider->retrieveById((string) $subject);
                },
                $app['request'],
                $provider,
            );

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }
}
