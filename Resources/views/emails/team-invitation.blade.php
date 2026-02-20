<x-mail::message>
# You've Been Invited to a Team

You have been invited to join the team **{{ $invitation->team->name }}**.

**Invited by:** {{ data_get($invitation->inviter, 'name', 'A manager') }}

**Email:** {{ $invitation->email }}

**Role:** {{ $invitation->role }}

This invitation expires on **{{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}**.

<x-mail::button :url="$acceptUrl">
Accept Team Invitation
</x-mail::button>

If you did not expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
