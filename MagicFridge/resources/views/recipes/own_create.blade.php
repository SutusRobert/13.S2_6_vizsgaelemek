@extends('layouts.app')
@section('title','Saját recept – MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card">
    <h2>Saját recept</h2>

    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <form method="post" action="{{ route('recipes.own.store') }}" class="mt-3">
      @csrf

      <label>Cím</label>
      <input type="text" name="title" value="{{ old('title') }}" required>

      <div class="mt-3">
        <label>Hozzávalók (soronként egy)</label>
        @for($i=0; $i<8; $i++)
          <input class="mt-2" type="text" name="ingredients[]" value="{{ old('ingredients.'.$i) }}">
        @endfor
      </div>

      <div class="mt-4" style="display:flex; gap:10px; flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">Mentés</button>
        <a class="btn btn-secondary" href="{{ route('recipes.index') }}">Mégse</a>
      </div>
    </form>
  </div>
</div>
@endsection
