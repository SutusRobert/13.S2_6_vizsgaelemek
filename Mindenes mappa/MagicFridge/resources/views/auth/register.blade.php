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
        <label>Teljes név</label>
        <input type="text" name="full_name" maxlength="40" required value="{{ old('full_name') }}">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" maxlength="40" required value="{{ old('email') }}">
      </div>

      <div class="form-group">
        <label>Jelszó</label>
        <input type="password" name="password" maxlength="40" required>
      </div>

      <div class="form-group">
        <label>Jelszó újra</label>
        <input type="password" name="password_confirmation" maxlength="40" required>
      </div>

      <button type="submit">Regisztráció</button>

      <p class="small mt-3">Van már fiókod? <a href="{{ route('login.form') }}">Lépj be itt.</a></p>
    </form>
  </div>
@endsection
