@extends('layouts.app')  {{-- change to your actual layout --}}

@section('content')
<div style="max-width:480px; margin:80px auto; text-align:center; font-family:Arial,sans-serif;">

    @if($status === 'success')
        <div style="font-size:56px;">✅</div>
        <h2 style="color:#16a34a;">E-mail verified!</h2>
        <p>Your account is now active. You can log in.</p>
        <a href="{{ route('login') }}"
           style="display:inline-block;margin-top:16px;padding:12px 28px;background:#16a34a;color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;">
            Go to Login
        </a>

    @elseif($status === 'already')
        <div style="font-size:56px;">👍</div>
        <h2>Already verified</h2>
        <p>Your e-mail was already confirmed. You can log in normally.</p>
        <a href="{{ route('login') }}"
           style="display:inline-block;margin-top:16px;padding:12px 28px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;">
            Log in
        </a>

    @else
        <div style="font-size:56px;">❌</div>
        <h2 style="color:#dc2626;">Invalid link</h2>
        <p>This verification link is invalid or has already been used.</p>
        <a href="{{ route('register') }}"
           style="display:inline-block;margin-top:16px;padding:12px 28px;background:#6b7280;color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;">
            Register again
        </a>
    @endif

</div>
@endsection
