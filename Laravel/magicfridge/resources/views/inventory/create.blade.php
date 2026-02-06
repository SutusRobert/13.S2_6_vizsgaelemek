@extends('layouts.app')
@section('title','Raktár – MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card">

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <h2>Raktár</h2>
    <p class="inv-muted mt-2">Adj hozzá új terméket a háztartás készletéhez.</p>

    <a class="btn btn-secondary mt-3" href="{{ route('inventory.list', ['hid' => $householdId]) }}">
      Készlet megnyitása
    </a>

    <form method="post" action="{{ route('inventory.store') }}" class="mt-4 inv-grid">
      @csrf

      <div class="form-group">
        <label>Háztartás</label>
        <select name="hid" required>
          @foreach($households as $h)
            <option value="{{ (int)$h->household_id }}" {{ (int)$h->household_id===(int)$householdId ? 'selected' : '' }}>
              {{ $h->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label>Név</label>
        <input type="text" name="name" required value="{{ old('name') }}">
      </div>

      <div class="form-group">
        <label>Kategória (opcionális)</label>
        <input type="text" name="category" value="{{ old('category') }}">
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Hely</label>
          <select name="location">
            <option value="fridge" {{ old('location')==='fridge' ? 'selected' : '' }}>Hűtő</option>
            <option value="freezer" {{ old('location')==='freezer' ? 'selected' : '' }}>Fagyasztó</option>
            <option value="pantry" {{ old('location','pantry')==='pantry' ? 'selected' : '' }}>Kamra</option>
          </select>
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Mennyiség</label>
          <input type="number" step="0.01" name="quantity" value="{{ old('quantity', 1) }}">
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Egység (opcionális)</label>
          <input type="text" name="unit" value="{{ old('unit') }}">
        </div>
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Lejárat (opcionális)</label>
          <input type="date" name="expires_at" value="{{ old('expires_at') }}">
        </div>

        <div class="form-group" style="margin-top:0; grid-column: span 2;">
          <label>Megjegyzés (opcionális)</label>
          <input type="text" name="note" value="{{ old('note') }}">
        </div>
      </div>

      <button type="submit">Hozzáadás</button>
    </form>

  </div>
</div>
@endsection
