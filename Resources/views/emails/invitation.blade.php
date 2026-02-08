<x-mail::message>
# You've Been Invited

You have been invited to join the platform.

**Invited by:** {{ $invitation->inviter->name ?? 'A manager' }}

**Email:** {{ $invitation->email }}

@if($invitation->resource_scope)
**Access scope:** {{ $invitation->resource_scope }}
@endif

This invitation will expire on **{{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}**.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

If you did not expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
