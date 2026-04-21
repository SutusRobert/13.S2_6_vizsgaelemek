@extends('layouts.app')
@section('title','Inventory - MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card">

    <h2>inventory </h2>
    <p class="inv-muted mt-2">Add a new item to the household inventory.</p>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <a class="btn btn-secondary mt-3" href="{{ route('inventory.list', ['hid' => $householdId]) }}">
      Open inventory
    </a>

    <form method="post" action="{{ route('inventory.store') }}" class="mt-4 inv-grid">
      @csrf

      <div class="form-group">
        <label>Household</label>
        <select name="hid" class="notranslate" translate="no" required>
          @foreach($households as $h)
            <option class="notranslate" translate="no" value="{{ (int)$h['household_id'] }}" {{ (int)$h['household_id']===(int)$householdId ? 'selected' : '' }}>
              {{ $h['name'] }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" required value="{{ old('name') }}">
      </div>

      <div class="form-group">
        <label>Category (optional)</label>
        <input type="text" name="category" value="{{ old('category') }}">
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Location</label>
          <select name="location" class="notranslate" translate="no">
            <option class="notranslate" translate="no" value="fridge" data-label-en="Fridge" data-label-hu="Hűtő" {{ old('location')==='fridge' ? 'selected' : '' }}>Fridge</option>
            <option class="notranslate" translate="no" value="freezer" data-label-en="Freezer" data-label-hu="Fagyasztó" {{ old('location')==='freezer' ? 'selected' : '' }}>Freezer</option>
            <option class="notranslate" translate="no" value="pantry" data-label-en="Pantry" data-label-hu="Kamra" {{ old('location','pantry')==='pantry' ? 'selected' : '' }}>Pantry</option>
          </select>
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Quantity</label>
          <input type="number" step="0.01" name="quantity" value="{{ old('quantity', 1) }}">
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Unit (optional)</label>
          <input type="text" name="unit" value="{{ old('unit') }}">
        </div>
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Expiration date (optional)</label>
          <input type="date" name="expires_at" value="{{ old('expires_at') }}">
        </div>

        <div class="form-group" style="margin-top:0; grid-column: span 2;">
          <label>Note (optional)</label>
          <input type="text" name="note" value="{{ old('note') }}">
        </div>
      </div>

      <button type="submit">Add</button>
    </form>

  </div>
</div>
@endsection
