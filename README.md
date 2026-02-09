# AuthModule

A self-contained Laravel module for **invitation-based user registration** with audit logging. Built with Clean Architecture (Ports & Adapters), making it easy to swap implementations without touching business logic.

## Features

- **Invitation workflow** -- managers invite users by email, users register via secure token
- **Role-based access** -- only managers can create invitations
- **Audit logging** -- every action (invite, accept, register) is logged with IP, user agent, and metadata
- **Queued emails** -- invitation emails are dispatched to the queue for async delivery
- **Secure tokens** -- 64-character hex tokens generated with `random_bytes()`
- **Idempotent acceptance** -- accepting an already-accepted invitation safely returns success
- **Resource scoping** -- optional `resource_scope` field for multi-tenant or permission scenarios

## Architecture

```
AuthModule/
├── Models/                  # Eloquent domain models
├── UseCases/                # Business logic (one class per use case)
│   ├── Register/
│   ├── InviteUser/
│   └── AcceptInvitation/
├── Ports/                   # Interfaces (contracts)
│   ├── Repositories/
│   └── Services/
├── Infrastructure/          # Concrete implementations
│   ├── Persistence/
│   ├── Services/
│   └── Mail/
├── Http/
│   ├── Controllers/
│   └── Routes/
├── Migrations/
├── Factories/
├── Resources/views/
└── Tests/
```

## Requirements

- PHP 8.1+
- Laravel 10+
- A configured mail driver (for sending invitations)
- A configured queue worker (invitations use `Mail::queue()`)

## Installation

### 1. Copy the module

Place the `AuthModule` directory inside your Laravel project. A common convention:

```
app/
  Modules/
    AuthModule/
      AuthModuleServiceProvider.php
      ...
```

### 2. Set the namespace

The module classes use the namespace matching their location. If you place it under `app/Modules/AuthModule`, ensure your `composer.json` autoloads it:

```json
{
  "autoload": {
    "psr-4": {
      "App\\Modules\\AuthModule\\": "app/Modules/AuthModule/"
    }
  }
}
```

Then run:

```bash
composer dump-autoload
```

### 3. Register the service provider

Add the service provider to your `bootstrap/providers.php` (Laravel 11+):

```php
return [
    // ...
    App\Modules\AuthModule\AuthModuleServiceProvider::class,
];
```

Or in `config/app.php` (Laravel 10):

```php
'providers' => [
    // ...
    App\Modules\AuthModule\AuthModuleServiceProvider::class,
],
```

The service provider automatically:
- Binds all interfaces to their Eloquent/Mail implementations
- Loads routes, migrations, and views

### 4. Run migrations

```bash
php artisan migrate
```

This creates three tables: `users`, `invitations`, and `auth_audit_logs`.

> **Note:** The module ships its own `users` table migration. If your project already has one, remove or adjust the module's `0001_01_01_000000_create_users_table.php` migration to avoid conflicts.

### 5. Configure the invitation URL

Invitation emails include a link pointing to your frontend. Set the base URL in your `.env`:

```env
FRONTEND_URL=https://yourapp.com
```

The generated link format is: `{FRONTEND_URL}/invitations/accept?token={TOKEN}`

Falls back to `APP_URL` if `FRONTEND_URL` is not set.

### 6. Configure mail and queue

Make sure your mail driver and queue worker are properly configured so invitation emails are sent:

```bash
# .env
MAIL_MAILER=smtp
QUEUE_CONNECTION=database   # or redis, sqs, etc.
```

```bash
php artisan queue:work
```

## Usage

### API Endpoints

| Method | URI | Description | Auth |
|--------|-----|-------------|------|
| `POST` | `/invitations/create` | Create an invitation | Yes (manager only) |
| `POST` | `/invitations/accept` | Accept an invitation | No |
| `POST` | `/users/register` | Register via invitation token | No |

### Create an invitation (manager only)

```http
POST /invitations/create
Content-Type: application/json
Authorization: Bearer {token}

{
  "email": "newuser@example.com",
  "resource_scope": "project-42"  // optional
}
```

**Responses:**

- `201` -- Invitation created, email queued
- `403` -- Authenticated user is not a manager
- `422` -- Validation error or email already registered

### Accept an invitation

```http
POST /invitations/accept
Content-Type: application/json

{
  "token": "a1b2c3d4..."
}
```

**Responses:**

- `200` -- Invitation accepted (idempotent)
- `404` -- Token not found
- `410` -- Invitation expired

### Register a new user

```http
POST /users/register
Content-Type: application/json

{
  "token": "a1b2c3d4...",
  "name": "Jane Doe",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Responses:**

- `201` -- User created
- `404` -- Token not found
- `410` -- Invitation expired or already used

### Typical flow

```
1. Manager  ->  POST /invitations/create  { email: "jane@co.com" }
                  Module generates token, queues email

2. Jane     <-  Receives email with link:
                  https://yourapp.com/invitations/accept?token=abc123...

3. Frontend ->  POST /invitations/accept  { token: "abc123..." }
                  Marks invitation as accepted

4. Frontend ->  POST /users/register  { token: "abc123...", name: "Jane", password: "..." }
                  Creates the user account
```

## Creating a manager

The first manager must be created manually (via tinker, a seeder, or a direct DB update):

```bash
php artisan tinker
```

```php
use App\Modules\AuthModule\Models\User;

User::create([
    'name'       => 'Admin',
    'email'      => 'admin@example.com',
    'password'   => 'your-secure-password',  // automatically hashed via cast
    'is_manager' => true,
]);
```

From there, managers can invite other users through the API.

## Customization

### Swapping implementations

The module uses interface bindings, so you can replace any implementation. Override the bindings in your own service provider:

```php
use App\Modules\AuthModule\Ports\Services\InvitationMailerInterface;
use App\CustomInvitationMailer;

public function register(): void
{
    $this->app->bind(InvitationMailerInterface::class, CustomInvitationMailer::class);
}
```

Available interfaces:

| Interface | Default Implementation | Purpose |
|-----------|----------------------|---------|
| `UserRepositoryInterface` | `EloquentUserRepository` | User persistence |
| `InvitationRepositoryInterface` | `EloquentInvitationRepository` | Invitation persistence |
| `TokenGeneratorInterface` | `SecureTokenGenerator` | Token generation |
| `AuditLoggerInterface` | `EloquentAuditLogger` | Audit logging |
| `InvitationMailerInterface` | `MailInvitationMailer` | Sending invitation emails |

### Publishing views

To customize the invitation email template, copy the view to your project's resources:

```bash
mkdir -p resources/views/vendor/authmodule/emails
cp app/Modules/AuthModule/Resources/views/emails/invitation.blade.php \
   resources/views/vendor/authmodule/emails/invitation.blade.php
```

Laravel will automatically use the vendor override.

## Testing

The module includes both unit and feature tests.

### Running tests

```bash
php artisan test app/Modules/AuthModule/Tests
```

Or with PHPUnit directly:

```bash
./vendor/bin/phpunit app/Modules/AuthModule/Tests
```

### Test coverage

**Unit tests** (mocked dependencies):
- `RegisterUserTest` -- registration with valid/invalid/expired/used tokens
- `InviteUserTest` -- manager permissions, duplicate email checks, resource scoping
- `AcceptInvitationTest` -- acceptance, expiration, idempotency

**Feature tests** (full HTTP with database):
- `UserControllerTest` -- registration endpoint, validation, audit log creation
- `InvitationControllerTest` -- invitation creation, acceptance, mail dispatch, auth guards

### Using factories in your own tests

```php
use App\Modules\AuthModule\Models\User;
use App\Modules\AuthModule\Models\Invitation;

// Create a manager
$manager = User::factory()->manager()->create();

// Create a pending invitation
$invitation = Invitation::factory()->create(['invited_by' => $manager->id]);

// Create an expired invitation
$expired = Invitation::factory()->expired()->create();

// Create an already-accepted invitation
$accepted = Invitation::factory()->accepted()->create();
```

## Database schema

### `users`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `name` | string | |
| `email` | string | unique |
| `password` | string | hashed |
| `is_manager` | boolean | default `false` |
| `email_verified_at` | timestamp | nullable |
| `remember_token` | string | nullable |
| `created_at` / `updated_at` | timestamps | |

### `invitations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `email` | string | indexed with `accepted_at` |
| `token` | string(64) | unique |
| `invited_by` | FK -> users | cascade on delete |
| `resource_scope` | string | nullable |
| `expires_at` | timestamp | default: 7 days from creation |
| `accepted_at` | timestamp | nullable |
| `created_at` / `updated_at` | timestamps | |

### `auth_audit_logs`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `action` | string | `invitation_created`, `invitation_accepted`, `user_registered` |
| `actor_id` | FK -> users | nullable, null on delete |
| `actor_email` | string | nullable |
| `target_email` | string | nullable |
| `invitation_id` | FK -> invitations | nullable, null on delete |
| `resource_scope` | string | nullable |
| `metadata` | json | nullable |
| `ip_address` | string | nullable |
| `user_agent` | text | nullable |
| `created_at` | timestamp | |

## License

This module is part of the Novos Horizontes project.
