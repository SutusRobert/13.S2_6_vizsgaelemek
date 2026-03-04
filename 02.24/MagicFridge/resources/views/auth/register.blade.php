@extends('layouts.app')

@section('title', 'Regisztráció – MagicFridge')

@section('content')
  <div class="card card-narrow">
    <h2>Regisztráció</h2>

    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.do') }}">
      @csrf

      <div class="form-group">
        <label>Full name</label>
        <input type="text" name="full_name" maxlength="40" required value="{{ old('full_name') }}">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" maxlength="40" required value="{{ old('email') }}">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" maxlength="40" required>
      </div>

      <div class="form-group">
        <label>Password again</label>
        <input type="password" name="password_confirmation" maxlength="40" required>
      </div>

      <button type="submit">Register</button>

      <p class="small mt-3">Already have an account?<a href="{{ route('login.form') }}">Log in here.</a></p>
    </form>
  </div>
@endsection
