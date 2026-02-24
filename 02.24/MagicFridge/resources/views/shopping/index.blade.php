@extends('layouts.app')
@section('title','Bevásárlólista – MagicFridge')

@section('content')
<div class="card" style="max-width: 1100px; width:100%;">

  <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
    <div>
      <h2 style="margin-bottom:6px;">Shopping list</h2>
      <div class="small">Household: <strong>{{ $householdName }}</strong></div>
    </div>

    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
      {{-- Háztartás választó (GET) --}}
      <form method="get" action="{{ route('shopping.index') }}" style="margin:0; display:flex; gap:10px; align-items:center;">
        <label class="small" style="opacity:.8;">Household</label>
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
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>

        <form method="post" action="{{ route('shopping.post') }}" style="margin:0;"
              onsubmit="return confirm('Biztos megveszed AZ ÖSSZES tételt? Ez fel is tölti a raktárba.');">
          @csrf
          <input type="hidden" name="action" value="buy_all">
          <input type="hidden" name="hid" value="{{ (int)$householdId }}">
          <button type="submit" class="btn btn-secondary">Buy all</button>
        </form>

        <form method="post" action="{{ route('shopping.post') }}" style="margin:0;"
              onsubmit="return confirm('Biztos törlöd AZ ÖSSZES tételt a bevásárlólistából?');">
          @csrf
          <input type="hidden" name="action" value="clear_all">
          <input type="hidden" name="hid" value="{{ (int)$householdId }}">
          <button type="submit" class="btn btn-secondary">Delete all</button>
        </form>

        <form method="post" action="{{ route('shopping.post') }}" style="margin:0;"
              onsubmit="return confirm('Do you want to delete all purchased items?');">
          @csrf
          <input type="hidden" name="action" value="clear_bought">
          <input type="hidden" name="hid" value="{{ (int)$householdId }}">
          <button type="submit" class="btn btn-secondary">Delete purchased items</button>
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

  {{-- New item --}}
  <h3 class="mt-4">New item</h3>
  <form method="post" action="{{ route('shopping.post') }}" class="sl-row mt-2">
    @csrf
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="hid" value="{{ (int)$householdId }}">

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Item</label>
      <input type="text" name="name" placeholder="for example : Bread " required>
    </div>

    <div class="form-group" style="flex: 0 0 140px;">
      <label>Quantity</label>
      <input type="number" step="0.01" name="quantity" value="1">
    </div>

    <div class="form-group" style="flex: 0 0 160px;">
      <label>Unit</label>
      <input type="text" name="unit" placeholder="pcs / kg / l">
    </div>

    <div class="form-group" style="flex: 0 0 170px;">
      <label>Location (inventory)</label>
      <select name="location">
        <option value="fridge">Fridge</option>
        <option value="freezer">Freezer</option>
        <option value="pantry" selected>Pantry</option>
      </select>
    </div>

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Note</label>
      <input type="text" name="note" placeholder="e.g. whole grain">
    </div>

    <div style="flex:0 0 auto;">
      <button type="submit">Add</button>
    </div>
  </form>

  <h3 class="mt-4">list</h3>

  <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
    @if(empty($items))
      <div class="small" style="opacity:.8;">No Item</div>
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
              {{ $bought ? 'Back' : 'Bought' }}
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
              <div class="sl-meta">Bought: {{ $boughtAt }}</div>
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
            <button type="submit" class="btn btn-secondary btn-mini">Delete</button>
          </form>
        </div>
      </div>
    @endforeach
  </div>

  <div class="small mt-4" style="opacity:.75;">
Tip: if you click “Purchased,” the item will automatically be added to the inventory in the selected location.
  </div>

</div>
@endsection
