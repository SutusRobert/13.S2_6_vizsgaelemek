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
        <h2 style="margin-bottom:6px;">Készlet</h2>
        <div class="small">Háztartás: <strong>{{ $householdName }}</strong></div>
      </div>
      <a class="btn btn-secondary" href="{{ route('inventory.create', ['hid' => $householdId]) }}">+ Új termék</a>
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
    <label>Keresés</label>
    <input type="text" name="q" value="{{ $q }}">
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
    <a class="btn btn-secondary" href="{{ route('inventory.list', ['hid'=>$householdId]) }}">Reset</a>
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
                      <option value="fridge"  {{ $it->location==='fridge'?'selected':'' }}>Hűtő</option>
                      <option value="freezer" {{ $it->location==='freezer'?'selected':'' }}>Fagyasztó</option>
                      <option value="pantry"  {{ $it->location==='pantry'?'selected':'' }}>Kamra</option>
                    </select>

                    <input type="number" step="0.01" name="quantity" value="{{ $it->quantity }}" style="max-width:110px;">
                    <input type="date" name="expires_at" value="{{ $it->expires_at }}" style="max-width:150px;">

                    <button type="submit" class="btn-mini">Mentés</button>
                  </form>
                  
                  <form method="post" action="{{ route('inventory.list.post') }}" style="display:inline; margin:0;" onsubmit="return confirm('Biztos törlöd?');">
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
