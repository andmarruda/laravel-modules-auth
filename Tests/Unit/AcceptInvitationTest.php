<?php

namespace App\Modules\AuthModule\Tests\Unit;

use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;
use App\Modules\AuthModule\UseCases\AcceptInvitation\AcceptInvitation;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AcceptInvitationTest extends TestCase
{
    private MockInterface $invitations;
    private MockInterface $auditLogger;
    private AcceptInvitation $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invitations = Mockery::mock(InvitationRepositoryInterface::class);
        $this->auditLogger = Mockery::mock(AuditLoggerInterface::class);
        $this->useCase = new AcceptInvitation($this->invitations, $this->auditLogger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_accept_valid_invitation(): void
    {
        $token = 'valid-token';
        $invitation = $this->createValidInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $this->invitations
            ->shouldReceive('markAsAccepted')
            ->with($invitation)
            ->once();

        $this->auditLogger
            ->shouldReceive('logInvitationAccepted')
            ->once();

        $result = $this->useCase->execute($token);

        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame($invitation, $result->invitation);
    }

    public function test_cannot_accept_invalid_token(): void
    {
        $token = 'invalid-token';

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn(null);

        $this->invitations->shouldNotReceive('markAsAccepted');
        $this->auditLogger->shouldNotReceive('logInvitationAccepted');

        $result = $this->useCase->execute($token);

        $this->assertFalse($result->success);
        $this->assertEquals('not_found', $result->error);
        $this->assertNull($result->invitation);
    }

    public function test_accept_already_used_invitation_is_idempotent(): void
    {
        $token = 'used-token';
        $invitation = $this->createUsedInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        // Should NOT mark as accepted again
        $this->invitations->shouldNotReceive('markAsAccepted');
        // Should NOT log again
        $this->auditLogger->shouldNotReceive('logInvitationAccepted');

        $result = $this->useCase->execute($token);

        // Idempotent: returns success
        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame($invitation, $result->invitation);
    }

    public function test_cannot_accept_expired_invitation(): void
    {
        $token = 'expired-token';
        $invitation = $this->createExpiredInvitation();

        $this->invitations
            ->shouldReceive('findByToken')
            ->with($token)
            ->once()
            ->andReturn($invitation);

        $this->invitations->shouldNotReceive('markAsAccepted');
        $this->auditLogger->shouldNotReceive('logInvitationAccepted');

        $result = $this->useCase->execute($token);

        $this->assertFalse($result->success);
        $this->assertEquals('expired', $result->error);
        $this->assertNull($result->invitation);
    }

    private function createValidInvitation(): Invitation
    {
        $invitation = new Invitation();
        $invitation->id = 1;
        $invitation->email = 'user@example.com';
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
