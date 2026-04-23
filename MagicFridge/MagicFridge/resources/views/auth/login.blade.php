@extends('layouts.app')

@section('title', 'Login - MagicFridge')

@section('content')
  <div class="card card-narrow">
    <h2>Login</h2>
    <p>Log in to your account to manage your recipes and household.</p>

    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif
    @if(session('status'))
      <div class="success mt-3">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login.do') }}">
      @csrf

      <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" maxlength="40" required value="{{ old('email') }}">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" maxlength="40" required>
      </div>

      <button type="submit">Login</button>

      <p class="small mt-3">No account yet? <a href="{{ route('register.form') }}">Register here.</a></p>
    </form>
  </div>
@endsection

