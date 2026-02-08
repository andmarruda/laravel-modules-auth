<?php

namespace App\Modules\AuthModule\Tests\Unit;

use App\Modules\AuthModule\Models\Invitation;
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Ports\Repositories\InvitationRepositoryInterface;
use App\Modules\AuthModule\Ports\Repositories\UserRepositoryInterface;
use App\Modules\AuthModule\Ports\Services\AuditLoggerInterface;
use App\Modules\AuthModule\Ports\Services\InvitationMailerInterface;
use App\Modules\AuthModule\Ports\Services\TokenGeneratorInterface;
use App\Modules\AuthModule\UseCases\InviteUser\InviteUser;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class InviteUserTest extends TestCase
{
    private MockInterface $invitations;
    private MockInterface $users;
    private MockInterface $tokenGenerator;
    private MockInterface $mailer;
    private MockInterface $auditLogger;
    private InviteUser $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invitations = Mockery::mock(InvitationRepositoryInterface::class);
        $this->users = Mockery::mock(UserRepositoryInterface::class);
        $this->tokenGenerator = Mockery::mock(TokenGeneratorInterface::class);
        $this->mailer = Mockery::mock(InvitationMailerInterface::class);
        $this->auditLogger = Mockery::mock(AuditLoggerInterface::class);

        $this->useCase = new InviteUser(
            $this->invitations,
            $this->users,
            $this->tokenGenerator,
            $this->mailer,
            $this->auditLogger,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_manager_can_invite_user(): void
    {
        $manager = $this->createManager();
        $email = 'newuser@example.com';
        $token = 'secure-token-123';

        $this->users
            ->shouldReceive('emailExists')
            ->with($email)
            ->once()
            ->andReturn(false);

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn($token);

        $expectedInvitation = new Invitation();
        $expectedInvitation->id = 1;
        $expectedInvitation->email = $email;
        $expectedInvitation->token = $token;

        $this->invitations
            ->shouldReceive('create')
            ->once()
            ->andReturn($expectedInvitation);

        $this->mailer
            ->shouldReceive('sendInvitation')
            ->with($expectedInvitation)
            ->once();

        $this->auditLogger
            ->shouldReceive('logInvitationCreated')
            ->once();

        $result = $this->useCase->execute($manager, $email);

        $this->assertTrue($result->success);
        $this->assertNull($result->error);
        $this->assertSame($expectedInvitation, $result->invitation);
    }

    public function test_manager_can_invite_user_with_resource_scope(): void
    {
        $manager = $this->createManager();
        $email = 'newuser@example.com';
        $resourceScope = 'project:123';

        $this->users
            ->shouldReceive('emailExists')
            ->andReturn(false);

        $this->tokenGenerator
            ->shouldReceive('generate')
            ->andReturn('token');

        $expectedInvitation = new Invitation();
        $expectedInvitation->resource_scope = $resourceScope;

        $this->invitations
            ->shouldReceive('create')
            ->withArgs(function ($data) use ($resourceScope) {
                return $data['resource_scope'] === $resourceScope;
            })
            ->andReturn($expectedInvitation);

        $this->mailer->shouldReceive('sendInvitation');
        $this->auditLogger->shouldReceive('logInvitationCreated');

        $result = $this->useCase->execute($manager, $email, $resourceScope);

        $this->assertTrue($result->success);
        $this->assertEquals($resourceScope, $result->invitation->resource_scope);
    }

    public function test_non_manager_cannot_invite_user(): void
    {
        $user = $this->createRegularUser();
        $email = 'newuser@example.com';

        $this->users->shouldNotReceive('emailExists');
        $this->tokenGenerator->shouldNotReceive('generate');
        $this->invitations->shouldNotReceive('create');
        $this->mailer->shouldNotReceive('sendInvitation');
        $this->auditLogger->shouldNotReceive('logInvitationCreated');

        $result = $this->useCase->execute($user, $email);

        $this->assertFalse($result->success);
        $this->assertEquals('forbidden', $result->error);
        $this->assertNull($result->invitation);
    }

    public function test_cannot_invite_already_registered_email(): void
    {
        $manager = $this->createManager();
        $email = 'existing@example.com';

        $this->users
            ->shouldReceive('emailExists')
            ->with($email)
            ->once()
            ->andReturn(true);

        $this->tokenGenerator->shouldNotReceive('generate');
        $this->invitations->shouldNotReceive('create');
        $this->mailer->shouldNotReceive('sendInvitation');
        $this->auditLogger->shouldNotReceive('logInvitationCreated');

        $result = $this->useCase->execute($manager, $email);

        $this->assertFalse($result->success);
        $this->assertEquals('email_already_registered', $result->error);
        $this->assertNull($result->invitation);
    }

    private function createManager(): User
    {
        $user = new User();
        $user->id = 1;
        $user->is_manager = true;

        return $user;
    }

    private function createRegularUser(): User
    {
        $user = new User();
        $user->id = 2;
        $user->is_manager = false;

        return $user;
    }
}
