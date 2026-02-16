<x-mail::message>
# Recuperacao de senha

Ola, {{ $user->name }}!

Use o codigo abaixo para redefinir sua senha:

<x-mail::panel>
<div style="text-align: center; font-size: 36px; font-weight: 700; letter-spacing: 8px; font-family: monospace; color: #0891b2;">
{{ $code }}
</div>
</x-mail::panel>

Este codigo expira em **10 minutos** ({{ $expiresAt->format('H:i') }}).

Se voce nao solicitou a recuperacao de senha, ignore este email.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
