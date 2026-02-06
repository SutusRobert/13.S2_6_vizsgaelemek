@extends('layouts.app')
@section('title','Raktár – MagicFridge')

@push('head')
<style>
  .bubbles{ position: fixed; inset:0; pointer-events:none; z-index:0; }
  .navbar, .dash-row { position: relative; z-index: 2; }
  .dash-row{ max-width:1750px; margin:0 auto; display:flex; gap:28px; padding:18px 28px 40px; box-sizing:border-box; }
  .dash-left,.dash-side{ width:420px; flex:0 0 420px; min-width:0; }
  .dash-mid{ flex:1 1 auto; min-width:560px; max-width:980px; }
  .main-wrapper{ margin:0; width:100%; }
</style>
@endpush

@section('content')
<div class="main-wrapper">
  <div class="card">

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <h3 class="mt-5">Készlet</h3>
    <p class="inv-muted mt-2">
      A teljes készletet külön oldalon mutatjuk, hogy jobban átlátható legyen.
    </p>

    <a class="btn btn-secondary mt-3" href="{{ route('inventory.list', ['hid' => $householdId]) }}">
      Készlet megnyitása
    </a>

    <form method="post" action="{{ route('inventory.store', ['hid' => $householdId]) }}" class="mt-2 inv-grid">
      @csrf

      <div class="form-group">
        <label>Név</label>
        <input type="text" name="name" required placeholder="pl. Tej" value="{{ old('name') }}">
      </div>

      <div class="form-group">
        <label>Kategória (opcionális)</label>
        <input type="text" name="category" placeholder="pl. tejtermék" value="{{ old('category') }}">
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Hely</label>
          <select name="location">
            <option value="fridge">Hűtő</option>
            <option value="freezer">Fagyasztó</option>
            <option value="pantry" selected>Kamra</option>
          </select>
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Mennyiség</label>
          <input type="number" step="0.01" name="quantity" value="{{ old('quantity', 1) }}">
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Egység (opcionális)</label>
          <input type="text" name="unit" placeholder="db / kg / l" value="{{ old('unit') }}">
        </div>
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Lejárat (opcionális)</label>
          <input type="date" name="expires_at" value="{{ old('expires_at') }}">
        </div>
        <div class="form-group" style="margin-top:0; grid-column: span 2;">
          <label>Megjegyzés (opcionális)</label>
          <input type="text" name="note" placeholder="pl. felbontva" value="{{ old('note') }}">
        </div>
      </div>

      <div>
        <button type="submit">Hozzáadás</button>
      </div>
    </form>

  </div>
</div>
@endsection
