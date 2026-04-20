@extends('layouts.app')

@section('title', 'Bejelentkezés – MagicFridge')

@section('content')
  <div class="card card-narrow">
    <h2>Bejelentkezés</h2>
    <p>Lépj be a fiókodba a receptek és háztartás kezeléséhez.</p>

    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.do') }}">
      @csrf

      <div class="form-group">
        <label>Email cím</label>
        <input type="email" name="email" maxlength="40" required value="{{ old('email') }}">
      </div>

      <div class="form-group">
        <label>Jelszó</label>
        <input type="password" name="password" maxlength="40" required>
      </div>

      <button type="submit">Belépés</button>

      <p class="small mt-3">Még nincs fiókod? <a href="{{ route('register.form') }}">Regisztrálj itt.</a></p>
    </form>
  </div>
@endsection
