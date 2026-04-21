@extends('layouts.app')
@section('title', ($recipe->title ?? 'Custom recipe') . ' - MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%; padding:22px;">

    {{-- Fejlec a recept cimevel, haztartasvalasztoval es navigacios gombokkal. --}}
    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <div class="small" style="opacity:.75; margin-bottom:6px;">Custom recipe details</div>
        <h2 style="margin:0;">{{ $recipe->title ?? 'Own recipes' }}</h2>

        @if(!empty($recipe->created_at))
          <div class="small" style="opacity:.75; margin-top:8px;">
            Saved: {{ $recipe->created_at }}
          </div>
        @endif

        <div class="mt-2">
          <label class="small" style="opacity:.85;">Household</label>
          <form method="get" action="{{ route('recipes.own.show', ['id' => (int)$recipe->id]) }}">
            <select name="hid" onchange="this.form.submit()">
              @foreach(($households ?? []) as $hh)
                <option value="{{ (int)$hh['household_id'] }}" {{ (int)$hh['household_id'] === (int)$hid ? 'selected' : '' }}>
                  {{ $hh['name'] }}
                </option>
              @endforeach
            </select>
          </form>
        </div>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="{{ route('recipes.index', ['hid' => (int)$hid]) }}">&larr; Recipes</a>
        <a class="btn btn-secondary" href="{{ route('shopping.index', ['hid' => (int)$hid]) }}">Shopping list</a>
      </div>
    </div>

    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    @if(($cook ?? '') === 'ok')
      <div class="success mt-3">Deducted from stock. Enjoy your meal!</div>
    @endif
    @if(($cook ?? '') === 'err')
      <div class="error mt-3">{{ $msg ?? 'Error occurred.' }}</div>
    @endif

    <div class="mt-3" style="width:100%; min-height:260px; border-radius:16px; overflow:hidden; background:rgba(0,0,0,.16); border:1px solid rgba(255,255,255,.12); display:flex; align-items:center; justify-content:center;">
      @if(!empty($recipe->image_path))
        <img src="{{ asset($recipe->image_path) }}" alt="" style="width:100%; height:300px; object-fit:cover;">
      @else
        <div style="opacity:.7; font-weight:900;">No image for this recipe.</div>
      @endif
      </div>

    <form method="post" action="{{ route('recipes.own.image', ['id' => (int)$recipe->id]) }}" enctype="multipart/form-data" class="mt-3" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      @csrf
      <input type="hidden" name="hid" value="{{ (int)$hid }}">
      <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" required style="max-width:320px;">
      <button type="submit" class="btn btn-secondary">
        {{ !empty($recipe->image_path) ? 'Change image' : 'Add image' }}
      </button>
    </form>

    {{-- Tartalmi racs: bal oldalt a keszletellenorzott hozzavalok, jobb oldalt gyors muveletek. --}}
    <div class="mt-4" style="display:grid; grid-template-columns: 1fr 320px; gap:18px;">
      <div style="border:1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.08); border-radius:18px; padding:16px;">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
          <div>
            <h3 style="margin:0;">Ingredients (with stock check)</h3>
            <div class="small" style="opacity:.75; margin-top:6px;">
              Missing: <b>{{ (int)($missingCount ?? 0) }}</b> db
            </div>
          </div>

          @if(((int)($missingCount ?? 0)) === 0 && !empty($ingredients))
            <form method="post" action="{{ route('recipes.own.consume', ['id' => (int)$recipe->id]) }}" style="margin:0;">
              @csrf
              <input type="hidden" name="hid" value="{{ (int)$hid }}">
              <button type="submit" class="btn btn-primary">I'll make the food</button>
            </form>
          @else
            <button type="button" class="btn btn-secondary" disabled>
              Missing {{ (int)($missingCount ?? 0) }}
            </button>
          @endif
        </div>

        @if(empty($ingredients) || count($ingredients) === 0)
          <div class="small mt-2" style="opacity:.8;">There are no ingredients.</div>
        @else
          <form method="post" action="{{ route('recipes.own.missingToShopping', ['id' => (int)$recipe->id]) }}">
            @csrf
            <input type="hidden" name="hid" value="{{ (int)$hid }}">

            <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
              @foreach($ingredients as $ing)
                @php
                  $has = (bool)($ing['has'] ?? false);
                  $name = (string)($ing['name'] ?? '');
                  $measure = (string)($ing['measure'] ?? '');
                @endphp

                <div style="
                  padding:12px;
                  border-radius:14px;
                  border:1px solid rgba(255,255,255,.10);
                  background: rgba(255,255,255,.06);
                  display:flex;
                  justify-content:space-between;
                  gap:12px;
                  align-items:center;
                ">
                  <div>
                    <div style="font-weight:900;">{{ $name }}</div>
                    @if($measure !== '')
                      <div class="small" style="opacity:.75;">{{ $measure }}</div>
                    @endif
                  </div>

                  <div style="display:flex; align-items:center; gap:10px;">
                    @if($has)
                      <span class="badge" style="opacity:.8;">Available</span>
                    @else
                      <span class="badge" style="background: rgba(255,80,80,.25); border:1px solid rgba(255,80,80,.35);">Missing</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>

            @if(((int)($missingCount ?? 0)) > 0)
              <button type="submit" class="btn btn-primary mt-3">
                Add missing ingredients to shopping list
              </button>
            @else
              <button type="button" class="btn btn-secondary mt-3" disabled>
                Everything is already in stock
              </button>
            @endif
          </form>
        @endif
      </div>

      {{-- Oldalso gyors muveletek. --}}
      <div style="border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); border-radius:18px; padding:16px;">
        <div style="font-weight:900; margin-bottom:10px;">Quick actions</div>

        <div style="display:grid; gap:10px;">
          <a class="btn btn-primary" href="{{ route('recipes.index', ['hid' => (int)$hid]) }}">My recipes</a>
          <a class="btn btn-secondary" href="{{ route('recipes.own.create', ['hid' => (int)$hid]) }}">+ New custom recipe</a>
          <a class="btn btn-secondary" href="{{ route('inventory.list', ['hid' => (int)$hid]) }}">Inventory</a>
          <a class="btn btn-secondary" href="{{ route('dashboard') }}">Dashboard</a>
        </div>

        <div class="small" style="opacity:.75; margin-top:12px; line-height:1.5;">
          The cook button appears when every ingredient is available in the selected household inventory.
        </div>
      </div>
    </div>

    {{-- Elkeszitesi leiras, ha a recepthez megadtak. --}}
    @if(!empty($recipe->instructions))
      <div class="mt-4" style="border:1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.08); border-radius:18px; padding:16px;">
        <h3 style="margin:0 0 12px;">Preparation</h3>
        <div style="white-space: pre-line; opacity:.9; line-height:1.7;">
          {{ $recipe->instructions }}
        </div>
      </div>
    @endif

  </div>
</div>

<style>
  @media (max-width: 980px){
    .card > div.mt-4 { grid-template-columns: 1fr !important; }
  }
</style>
@endsection
