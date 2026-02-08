<?php

namespace App\Modules\AuthModule\Tests\Unit;

use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;
use App\Modules\AuthModule\UseCases\Register\RegisterUser;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    private MockInterface $invitations;
    private MockInterface $users;
    private MockInterface $auditLogger;
    private RegisterUser $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invitations = Mockery::mock(InvitationRepositoryInterface::class);
        $this->users = Mockery::mock(UserRepositoryInterface::class);
        $this->auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $this->useCase = new RegisterUser($this->invitations, $this->users, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_register_with_valid_invitation(): void
    {
        $token = 'valid-token';
        $name = 'John Doe';
        $password = 'SecurePass123!';
        $invitation = $this->createValidInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $expectedUser = new User();
        $expectedUser->id = 1;
        $expectedUser->name = $name;
        $expectedUser->email = $invitation->email;

        $this->users
            ->shouldReceive('create')
            ->once()
            ->with([
                'name' => $name,
                'email' => $invitation->email,
                'password' => $password,
            ])
            ->andReturn($expectedUser);

        $this->invitations
            ->shouldReceive('markAsAccepted')
            ->with($invitation)
            ->once();

        $this->auditLogger
            ->shouldReceive('logUserRegistered')
            ->once();

        $result = $this->useCase->execute($token, $name, $password);

        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame($expectedUser, $result->user);
    }

    public function test_cannot_register_with_invalid_token(): void
    {
        $token = 'invalid-token';

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn(null);

        $this->users->shouldNotReceive('create');
        $this->invitations->shouldNotReceive('markAsAccepted');
        $this->auditLogger->shouldNotReceive('logUserRegistered');

        $result = $this->useCase->execute($token, 'John', 'password');

        $this->assertFalse($result->success);
        $this->assertEquals('invitation_not_found', $result->error);
        $this->assertNull($result->user);
    }

    public function test_cannot_register_with_used_invitation(): void
    {
        $token = 'used-token';
        $invitation = $this->createUsedInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $this->users->shouldNotReceive('create');
        $this->invitations->shouldNotReceive('markAsAccepted');
        $this->auditLogger->shouldNotReceive('logUserRegistered');

        $result = $this->useCase->execute($token, 'John', 'password');

        $this->assertFalse($result->success);
        $this->assertEquals('invitation_already_used', $result->error);
        $this->assertNull($result->user);
    }

    public function test_cannot_register_with_expired_invitation(): void
    {
        $token = 'expired-token';
        $invitation = $this->createExpiredInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $this->users->shouldNotReceive('create');
        $this->invitations->shouldNotReceive('markAsAccepted');
        $this->auditLogger->shouldNotReceive('logUserRegistered');

        $result = $this->useCase->execute($token, 'John', 'password');

        $this->assertFalse($result->success);
        $this->assertEquals('invitation_expired', $result->error);
        $this->assertNull($result->user);
    }

    private function createValidInvitation(): Invitation
    {
        $invitation = new Invitation();
        $invitation->id = 1;
        $invitation->email = 'newuser@example.com';
        $invitation->token = 'valid-token';
        $invitation->accepted_at = null;
        $invitation->expires_at = Carbon::now()->addDays(7);

        return $invitation;
    }

    private function createUsedInvitation(): Invitation
    {
        $invitation = new Invitation();
        $invitation->id = 2;
        $invitation->email = 'user@example.com';
        $invitation->token = 'used-token';
        $invitation->accepted_at = Carbon::now();
        $invitation->expires_at = Carbon::now()->addDays(7);

        return $invitation;
    }

    private function createExpiredInvitation(): Invitation
    {
        $invitation = new Invitation();
        $invitation->id = 3;
        $invitation->email = 'user@example.com';
        $invitation->token = 'expired-token';
        $invitation->accepted_at = null;
        $invitation->expires_at = Carbon::now()->subDay();

        return $invitation;
    }
}
