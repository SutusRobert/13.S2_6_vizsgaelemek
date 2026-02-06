@extends('layouts.app')
@section('title','Készlet – MagicFridge')

@php
  $today = new DateTime('today');
  $soon = (clone $today)->modify('+3 days');
@endphp

@push('head')
<style>
  .bubbles{ position: fixed; inset:0; pointer-events:none; z-index:0; }
  .navbar, .dash-row { position: relative; z-index: 2; }
  .dash-row{ max-width:1750px; margin:0 auto; display:flex; gap:28px; padding:18px 28px 40px; box-sizing:border-box; }
  .main-wrapper{ margin:0; width:100%; }
</style>
@endpush

@section('content')
<div class="main-wrapper">
  <div class="card" style="max-width: 1200px; width: 100%;">

    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
      <div>
        <h2 style="margin-bottom:6px;">Készlet</h2>
        <div class="small">Háztartás: <strong>{{ $householdName }}</strong></div>
      </div>
      <div style="display:flex; gap:10px; align-items:center;">
        <a class="btn btn-secondary" href="{{ route('inventory.create', ['hid' => $householdId]) }}">+ Új termék</a>
      </div>
    </div>

    <div class="mt-3">
      <form method="get" action="{{ route('inventory.list') }}" style="margin:0;">
        <label class="small" style="opacity:.8;">Háztartás</label>
        <select name="hid" onchange="this.form.submit()">
          @foreach($households as $hh)
            <option value="{{ $hh['household_id'] }}" {{ (int)$hh['household_id'] === (int)$householdId ? 'selected' : '' }}>
              {{ $hh['name'] }}
            </option>
          @endforeach
        </select>

        @if($q !== '') <input type="hidden" name="q" value="{{ $q }}"> @endif
        @if($loc !== '') <input type="hidden" name="loc" value="{{ $loc }}"> @endif
      </form>
    </div>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div class="error mt-3">
        <strong>Hiba:</strong>
        <ul style="margin:8px 0 0 18px;">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="get" action="{{ route('inventory.list') }}" class="mt-4 inv-filters">
      <input type="hidden" name="hid" value="{{ $householdId }}">

      <div class="form-group" style="margin-top:0;">
        <label>Keresés</label>
        <input type="text" name="q" placeholder="pl. tej, tojás..." value="{{ $q }}">
      </div>

      <div class="form-group" style="margin-top:0;">
        <label>Hely</label>
        <select name="loc">
          <option value="">Minden</option>
          <option value="fridge"  {{ $loc==='fridge'?'selected':'' }}>Hűtő</option>
          <option value="freezer" {{ $loc==='freezer'?'selected':'' }}>Fagyasztó</option>
          <option value="pantry"  {{ $loc==='pantry'?'selected':'' }}>Kamra</option>
        </select>
      </div>

      <div style="display:flex; gap:10px; align-items:end;">
        <button type="submit">Szűrés</button>
        <a class="btn btn-secondary" href="{{ route('inventory.list', ['hid' => $householdId]) }}">Reset</a>
      </div>
    </form>

    <div class="mt-4">
      @if(empty($items))
        <p class="inv-muted">Még nincs termék a raktárban.</p>
      @else
        <table class="inv-table">
          <thead>
            <tr>
              <th>Termék</th>
              <th>Hely</th>
              <th>Mennyiség</th>
              <th>Lejárat</th>
              <th style="text-align:right;">Művelet</th>
            </tr>
          </thead>
          <tbody>
          @foreach($items as $it)
            @php
              $badgeClass = 'badge-ok'; $badgeText = 'OK';
              if (!empty($it->expires_at)) {
                $d = new DateTime($it->expires_at);
                if ($d < $today) { $badgeClass='badge-danger'; $badgeText='Lejárt'; }
                elseif ($d <= $soon) { $badgeClass='badge-warn'; $badgeText='Hamarosan'; }
              }
              $locText = $it->location==='fridge' ? 'Hűtő' : ($it->location==='freezer' ? 'Fagyasztó' : 'Kamra');
            @endphp

            <tr>
              <td>
                <strong>{{ $it->name }}</strong>
                @if(!empty($it->category))
                  <div class="inv-muted">{{ $it->category }}</div>
                @endif
              </td>
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
              <td>
                <div class="inv-actions">

                  {{-- UPDATE --}}
                  <form method="post" action="{{ route('inventory.list.post') }}" style="display:flex; gap:8px; align-items:center; margin:0;">
                    @csrf
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="{{ (int)$it->id }}">
                    <input type="hidden" name="hid" value="{{ (int)$householdId }}">
                    @if($q !== '') <input type="hidden" name="q" value="{{ $q }}"> @endif
                    @if($loc !== '') <input type="hidden" name="loc" value="{{ $loc }}"> @endif

                    <select name="location" style="min-width:130px;">
                      <option value="fridge"  {{ $it->location==='fridge'?'selected':'' }}>Hűtő</option>
                      <option value="freezer" {{ $it->location==='freezer'?'selected':'' }}>Fagyasztó</option>
                      <option value="pantry"  {{ $it->location==='pantry'?'selected':'' }}>Kamra</option>
                    </select>

                    <input type="number" step="0.01" name="quantity" value="{{ $it->quantity }}" style="max-width:110px;">
                    <input type="date" name="expires_at" value="{{ $it->expires_at }}" style="max-width:150px;">

                    <button type="submit" class="btn-mini">Mentés</button>
                  </form>

                  {{-- DELETE --}}
                  <form method="post" action="{{ route('inventory.list.post') }}" style="margin:0;" onsubmit="return confirm('Biztos törlöd?');">
                    @csrf
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="{{ (int)$it->id }}">
                    <input type="hidden" name="hid" value="{{ (int)$householdId }}">
                    @if($q !== '') <input type="hidden" name="q" value="{{ $q }}"> @endif
                    @if($loc !== '') <input type="hidden" name="loc" value="{{ $loc }}"> @endif
                    <button type="submit" class="btn btn-secondary btn-mini">Törlés</button>
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
