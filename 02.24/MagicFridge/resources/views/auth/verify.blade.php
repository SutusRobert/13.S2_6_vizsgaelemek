@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Email Verification</div>
                <div class="card-body">
                    @if($status == 'success')
                        <div class="alert alert-success">
                            Your email has been verified successfully! You can now <a href="{{ route('login.form') }}">log in</a>.
                        </div>
                    @elseif($status == 'already')
                        <div class="alert alert-info">
                            Your email is already verified. You can <a href="{{ route('login.form') }}">log in</a>.
                        </div>
                    @elseif($status == 'invalid')
                        <div class="alert alert-danger">
                            Invalid verification link. Please check your email for the correct link or register again.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection