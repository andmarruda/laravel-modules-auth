<?php

namespace Andmarruda\AuthModule;

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
use Andmarruda\AuthModule\Ports\Services\OtpMailerInterface;
use Andmarruda\AuthModule\Ports\Services\TokenGeneratorInterface;
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
    }

    public function boot(): void
    {
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
}
