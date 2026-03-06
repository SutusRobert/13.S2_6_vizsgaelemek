@extends('layouts.app')
@section('title','Bevásárlólista – MagicFridge')

@section('content')
<div class="card" style="max-width: 1100px; width:100%;">

  <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
    <div>
      <h2 style="margin-bottom:6px;">Bevásárlólista</h2>
      <div class="small">Háztartás: <strong>{{ $householdName }}</strong></div>
    </div>

    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
      {{-- Háztartás választó (GET) --}}
      <form method="get" action="{{ route('shopping.index') }}" style="margin:0; display:flex; gap:10px; align-items:center;">
        <label class="small" style="opacity:.8;">Háztartás</label>
        <select name="hid" onchange="this.form.submit()">
          @foreach($households as $hh)
            @php $hidOpt = (int)$hh->household_id; @endphp
            <option value="{{ $hidOpt }}" {{ $hidOpt === (int)$householdId ? 'selected' : '' }}>
              {{ $hh->name }}
            </option>
          @endforeach
        </select>
      </form>

      {{-- Jobb felső gombsor --}}
      <div class="sl-printbar">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Nyomtatás</button>

        <form method="post" action="{{ route('shopping.post') }}" style="margin:0;"
              onsubmit="return confirm('Biztos megveszed AZ ÖSSZES tételt? Ez fel is tölti a raktárba.');">
          @csrf
          <input type="hidden" name="action" value="buy_all">
          <input type="hidden" name="hid" value="{{ (int)$householdId }}">
          <button type="submit" class="btn btn-secondary">Összes megvétele</button>
        </form>

        <form method="post" action="{{ route('shopping.post') }}" style="margin:0;"
              onsubmit="return confirm('Biztos törlöd AZ ÖSSZES tételt a bevásárlólistából?');">
          @csrf
          <input type="hidden" name="action" value="clear_all">
          <input type="hidden" name="hid" value="{{ (int)$householdId }}">
          <button type="submit" class="btn btn-secondary">Összes törlése</button>
        </form>

        <form method="post" action="{{ route('shopping.post') }}" style="margin:0;"
              onsubmit="return confirm('Törlöd az összes megvett tételt?');">
          @csrf
          <input type="hidden" name="action" value="clear_bought">
          <input type="hidden" name="hid" value="{{ (int)$householdId }}">
          <button type="submit" class="btn btn-secondary">Megvett törlése</button>
        </form>
      </div>
    </div>
  </div>

  {{-- Flash üzenetek --}}
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

  {{-- Új tétel --}}
  <h3 class="mt-4">Új tétel</h3>
  <form method="post" action="{{ route('shopping.post') }}" class="sl-row mt-2">
    @csrf
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="hid" value="{{ (int)$householdId }}">

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Termék</label>
      <input type="text" name="name" placeholder="pl. kenyér" required>
    </div>

    <div class="form-group" style="flex: 0 0 140px;">
      <label>Mennyiség</label>
      <input type="number" step="0.01" name="quantity" value="1">
    </div>

    <div class="form-group" style="flex: 0 0 160px;">
      <label>Egység</label>
      <input type="text" name="unit" placeholder="db / kg / l">
    </div>

    <div class="form-group" style="flex: 0 0 170px;">
      <label>Hely (raktár)</label>
      <select name="location">
        <option value="fridge">Hűtő</option>
        <option value="freezer">Fagyasztó</option>
        <option value="pantry" selected>Kamra</option>
      </select>
    </div>

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Megjegyzés</label>
      <input type="text" name="note" placeholder="pl. teljes kiőrlésű">
    </div>

    <div style="flex:0 0 auto;">
      <button type="submit">Hozzáadás</button>
    </div>
  </form>

  <h3 class="mt-4">Lista</h3>

  <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
    @if(empty($items))
      <div class="small" style="opacity:.8;">Nincs tétel.</div>
    @endif

    @foreach($items as $it)
      @php
        $bought = ((int)($it->is_bought ?? 0) === 1);

        $loc = (string)($it->location ?? 'pantry');
        $locLabel = $loc === 'fridge' ? 'Hűtő' : ($loc === 'freezer' ? 'Fagyasztó' : 'Kamra');

        $qty = (string)($it->quantity ?? '1');
        $unit = (string)($it->unit ?? '');
        $note = (string)($it->note ?? '');
        $boughtAt = (string)($it->bought_at ?? '');
      @endphp

      <div class="sl-item">
        <div class="sl-left">
          {{-- Toggle --}}
          <form method="post" action="{{ route('shopping.post') }}" style="margin:0;">
            @csrf
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="hid" value="{{ (int)$householdId }}">
            <input type="hidden" name="id" value="{{ (int)$it->id }}">
            <input type="hidden" name="to" value="{{ $bought ? 0 : 1 }}">
            <button type="submit" class="btn btn-secondary btn-mini">
              {{ $bought ? 'Vissza' : 'Megvett' }}
            </button>
          </form>

          <div>
            <div class="sl-name {{ $bought ? 'sl-done' : '' }}">
              {{ $it->name }}
              <span class="small" style="opacity:.75;">
                — {{ $qty }} {{ $unit }}
              </span>
              <span class="small" style="opacity:.75;"> • {{ $locLabel }}</span>
            </div>

            @if($note !== '')
              <div class="sl-meta">{{ $note }}</div>
            @endif

            @if($bought && $boughtAt !== '')
              <div class="sl-meta">Megvéve: {{ $boughtAt }}</div>
            @endif
          </div>
        </div>

        <div class="sl-actions">
          {{-- Delete --}}
          <form method="post" action="{{ route('shopping.post') }}" style="margin:0;" onsubmit="return confirm('Biztos törlöd?');">
            @csrf
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="hid" value="{{ (int)$householdId }}">
            <input type="hidden" name="id" value="{{ (int)$it->id }}">
            <button type="submit" class="btn btn-secondary btn-mini">Törlés</button>
          </form>
        </div>
      </div>
    @endforeach
  </div>

  <div class="small mt-4" style="opacity:.75;">
    Tipp: ha “Megvett”-re nyomsz, a tétel automatikusan felkerül a raktárba a kiválasztott helyre.
  </div>

</div>
@endsection
