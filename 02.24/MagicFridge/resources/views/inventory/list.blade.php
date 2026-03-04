@extends('layouts.app')
@section('title','Készlet – MagicFridge')

@php
  $today = new DateTime('today');
  $soon = (clone $today)->modify('+3 days');
@endphp

@section('content')
<div class="main-wrapper">
  <div class="card" style="max-width:1200px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
      <div>
        <h2 style="margin-bottom:6px;">Inventory</h2>
        <div class="small">Household: <strong>{{ $householdName }}</strong></div>
      </div>
      <a class="btn btn-secondary" href="{{ route('inventory.create', ['hid' => $householdId]) }}">+ New item</a>
    </div>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    <form method="get" action="{{ route('inventory.list') }}" class="mt-4 inv-filters">
  <input type="hidden" name="hid" value="{{ $householdId }}">

  <div class="form-group" style="margin-top:0;">
    <label>Search</label>
    <input type="text" name="q" value="{{ $q }}">
  </div>

  <div class="form-group" style="margin-top:0;">
    <label>Location</label>
    <select name="loc">
      <option value="">All of them</option>
      <option value="fridge"  {{ $loc==='fridge'?'selected':'' }}>Fridge</option>
      <option value="freezer" {{ $loc==='freezer'?'selected':'' }}>Freezer</option>
      <option value="pantry"  {{ $loc==='pantry'?'selected':'' }}>Pantry</option>
    </select>
  </div>

  <div style="display:flex; gap:10px; align-items:end;">
    <button type="submit">Filter</button>
    <a class="btn btn-secondary" href="{{ route('inventory.list', ['hid'=>$householdId]) }}">Reset</a>
  </div>
</form>

{{--Household selector (Warehouse) --}}
<div style="min-width:260px;">
  <label class="small" style="opacity:.85;">Household</label>

  <form method="get" action="{{ route('inventory.list') }}">
    {{-- megtartjuk a szűrőket váltáskor --}}
    <input type="hidden" name="q" value="{{ (string)($q ?? '') }}">
    <input type="hidden" name="loc" value="{{ (string)($loc ?? '') }}">

    <select name="hid" onchange="this.form.submit()">
      @foreach(($households ?? []) as $hh)
        @php
          $hhId = (int)($hh['household_id'] ?? $hh->household_id ?? 0);
          $hhName = (string)($hh['name'] ?? $hh->name ?? '');
        @endphp
        <option value="{{ $hhId }}" {{ $hhId === (int)($householdId ?? $householdId ?? 0) ? 'selected' : '' }}>
          {{ $hhName }}
        </option>
      @endforeach
    </select>
  </form>
</div>


    <div class="mt-4">
      @if(empty($items))
        <p class="inv-muted">There are no items in the warehouse yet.</p>
      @else
        <table class="inv-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Location</th>
              <th>Quantity</th>
              <th>Expiration date</th>
              <th style="text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
          @foreach($items as $it)
            @php
              $badgeClass = 'badge-ok'; $badgeText = 'OK';
              if (!empty($it->expires_at)) {
                $d = new DateTime($it->expires_at);
                if ($d < $today) { $badgeClass='badge-danger'; $badgeText='Expired'; }
                elseif ($d <= $soon) { $badgeClass='badge-warn'; $badgeText='Soon'; }
              }
              $locText = $it->location==='fridge' ? 'Fridge' : ($it->location==='freezer' ? 'Freezer' : 'Pantry');
            @endphp

            <tr>
              <td><strong>{{ $it->name }}</strong></td>
              <td>{{ $locText }}</td>
              <td>{{ $it->quantity }} {{ $it->unit }}</td>
              <td>
                @if(!empty($it->expires_at))
                  <span class="badge {{ $badgeClass }}">{{ $badgeText }}</span>
                  <span class="inv-muted"> {{ $it->expires_at }}</span>
                @else
                  <span class="inv-muted">—</span>
                @endif
              </td>
              <td style="text-align:right;">
                <div class="inv-actions">

                  <form method="post" action="/inventory/list" style="display:inline-flex; gap:8px; align-items:center; margin:0;">
                    @csrf
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="{{ (int)$it->id }}">
                    <input type="hidden" name="hid" value="{{ (int)$householdId }}">
                    @if($q !== '') <input type="hidden" name="q" value="{{ $q }}"> @endif
                    @if($loc !== '') <input type="hidden" name="loc" value="{{ $loc }}"> @endif

                    <select name="location">
                      <option value="fridge"  {{ $it->location==='fridge'?'selected':'' }}>Fridge</option>
                      <option value="freezer" {{ $it->location==='freezer'?'selected':'' }}>Freezer</option>
                      <option value="pantry"  {{ $it->location==='pantry'?'selected':'' }}>Pantry</option>
                    </select>

                    <input type="number" step="0.01" name="quantity" value="{{ $it->quantity }}" style="max-width:110px;">
                    <input type="date" name="expires_at" value="{{ $it->expires_at }}" style="max-width:150px;">

                    <button type="submit" class="btn-mini">Save</button>
                  </form>
                  
                  <form method="post" action="{{ route('inventory.list.post') }}" style="display:inline; margin:0;" onsubmit="return confirm('Are you sure you want to delete it?');">
                    @csrf
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="{{ (int)$it->id }}">
                    <input type="hidden" name="hid" value="{{ (int)$householdId }}">
                    @if($q !== '') <input type="hidden" name="q" value="{{ $q }}"> @endif
                    @if($loc !== '') <input type="hidden" name="loc" value="{{ $loc }}"> @endif
                    <button type="submit" class="btn btn-secondary btn-mini">Delete</button>
                  </form>
                  
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @endif
    </div>

  </div>
</div>
@endsection
