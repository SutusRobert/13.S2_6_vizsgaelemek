@extends('layouts.app')

@section('title', 'Email Verification')

@section('content')
<div style="max-width:520px; margin:80px auto; text-align:center; font-family:Arial,sans-serif; padding:34px 30px; border-radius:8px; background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.2); box-shadow:0 18px 50px rgba(0,0,0,0.35);">
    @if($status === 'success')
        <div style="font-size:44px; color:#bfdbfe;">OK</div>
        <h2 style="color:#fff;">E-mail verified</h2>
        <p style="color:#cbd5e1;">Your account is now active. You can log in.</p>
        <a href="{{ route('login.form') }}"
           style="display:inline-block;margin-top:16px;padding:12px 28px;background:linear-gradient(135deg,#6366f1,#2563eb);color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;box-shadow:0 0 18px rgba(96,165,250,0.55);">
            Go to Login
        </a>
    @elseif($status === 'already')
        <div style="font-size:44px; color:#bfdbfe;">OK</div>
        <h2 style="color:#fff;">Already verified</h2>
        <p style="color:#cbd5e1;">Your e-mail was already confirmed. You can log in normally.</p>
        <a href="{{ route('login.form') }}"
           style="display:inline-block;margin-top:16px;padding:12px 28px;background:linear-gradient(135deg,#6366f1,#2563eb);color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;box-shadow:0 0 18px rgba(96,165,250,0.55);">
            Log in
        </a>
    @else
        <div style="font-size:44px; color:#fca5a5;">!</div>
        <h2 style="color:#fff;">Invalid link</h2>
        <p style="color:#cbd5e1;">This verification link is invalid or has already been used.</p>
        <a href="{{ route('register.form') }}"
           style="display:inline-block;margin-top:16px;padding:12px 28px;background:rgba(255,255,255,0.18);color:#fff;border-radius:6px;text-decoration:none;font-weight:bold;border:1px solid rgba(255,255,255,0.18);">
            Register again
        </a>
    @endif
</div>
@endsection
