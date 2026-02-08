<?php

namespace App\Modules\AuthModule;

use App\Modules\AuthModule\Infrastructure\Persistence\EloquentInvitationRepository;
use App\Modules\AuthModule\Infrastructure\Persistence\EloquentUserRepository;
use App\Modules\AuthModule\Infrastructure\Services\EloquentAuditLogger;
use App\Modules\AuthModule\Infrastructure\Services\MailInvitationMailer;
use App\Modules\AuthModule\Infrastructure\Services\SecureTokenGenerator;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;
use App\Modules\AuthModule\Ports\Services\InvitationMailerInterface;
use App\Modules\AuthModule\Ports\Services\TokenGeneratorInterface;
use Illuminate\Support\ServiceProvider;

class AuthModuleServiceProvider extends ServiceProvider
{
    public array $bindings = [
        InvitationRepositoryInterface::class => EloquentInvitationRepository::class,
        UserRepositoryInterface::class => EloquentUserRepository::class,
        AuditLoggerInterface::class => EloquentAuditLogger::class,
        InvitationMailerInterface::class => MailInvitationMailer::class,
        TokenGeneratorInterface::class => SecureTokenGenerator::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/Http/Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'authmodule');
    }
}
