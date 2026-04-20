@extends('layouts.app')
@section('title', ($title ?? 'Recipe').' - Recipes')

@section('content')
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <h2 style="margin:0;">{{ $title }}</h2>

        <div class="mt-2">
          <label class="small" style="opacity:.85;">Household</label>
          <form method="get" action="{{ route('recipes.show', ['id'=>$mealId]) }}">
            <select name="hid" onchange="this.form.submit()">
              @foreach($households as $hh)
                <option value="{{ (int)$hh['household_id'] }}" {{ (int)$hh['household_id']===(int)$hid ? 'selected':'' }}>
                  {{ $hh['name'] }}
                </option>
              @endforeach
            </select>
          </form>
        </div>
      </div>

      <div style="display:flex; gap:10px;">
        <a class="btn btn-secondary" href="{{ route('recipes.index', ['hid'=>$hid]) }}">Back</a>
        <a class="btn btn-secondary" href="{{ route('shopping.index', ['hid'=>$hid]) }}">Shopping list</a>
      </div>
    </div>

    {{-- Sikeres vagy hibás művelet üzenetei --}}
    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    {{-- Főzés utáni készletlevonás eredménye --}}
    @if(($cook ?? '') === 'ok')
      <div class="success mt-3">✅ Deducted from stock. Enjoy your meal!!</div>
    @endif
    @if(($cook ?? '') === 'err')
      <div class="error mt-3">❌ {{ $msg ?? 'Error acoured.' }}</div>
    @endif

    @if(!empty($image))
      <div class="mt-3">
        <img src="{{ $image }}" alt="" style="width:100%; max-height:260px; object-fit:cover; border-radius:16px;">
      </div>
    @endif

    <div class="mt-4">
      <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div>
          <h3 style="margin:0;">Ingredients (with stock check)</h3>
          <div class="small" style="opacity:.75; margin-top:6px;">
            Missing: <b>{{ (int)($missingCount ?? 0) }}</b> db
          </div>
        </div>

        {{-- Csak akkor engedjük a főzés gombot, ha a szerveroldali készletellenőrzés szerint nincs hiány. --}}
        @if(((int)($missingCount ?? 0)) === 0)
          <form method="post" action="{{ route('recipes.consume', ['id'=>$mealId]) }}" style="margin:0;">
            @csrf
            <input type="hidden" name="hid" value="{{ (int)$hid }}">
            <button type="submit" class="btn btn-primary">🍳I’ll make the food (deduct from stock).</button>
          </form>
        @else
          <button type="button" class="btn btn-secondary" disabled>
            🍳 I’ll make the food (missing {{ (int)($missingCount ?? 0) }})
          </button>
        @endif
      </div>

      <form method="post" action="{{ route('recipes.missingToShopping', ['id'=>$mealId]) }}">
        @csrf
        <input type="hidden" name="hid" value="{{ (int)$hid }}">

        <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
          @foreach($ingredients as $idx => $ing)
            @php
              $has = (bool)($ing['has'] ?? false);

              // Ha később lesz fordított név, azt jelenítjük meg, különben marad az API neve.
              $name = (string)($ing['name_hu'] ?? $ing['name'] ?? '');

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

                  {{-- A checkbox kikapcsolásakor kiürítjük a hidden mezőket, így az adott tétel nem kerül feladásra. --}}
                  <input type="hidden" name="items[{{ $idx }}][name]" value="{{ $name }}">
                  <input type="hidden" name="items[{{ $idx }}][measure]" value="{{ $measure }}">

                  <input type="checkbox" checked
                         onchange="
                           const row=this.closest('div');
                           const h1=row.querySelector('input[name=&quot;items[{{ $idx }}][name]&quot;]');
                           const h2=row.querySelector('input[name=&quot;items[{{ $idx }}][measure]&quot;]');
                           if(!this.checked){ h1.value=''; h2.value=''; } else { h1.value='{{ addslashes($name) }}'; h2.value='{{ addslashes($measure) }}'; }
                         ">
                @endif
              </div>
            </div>
          @endforeach
        </div>

        <button type="submit" class="btn btn-primary mt-3">
          🛒 Add missing ingredients to shopping list
        </button>
      </form>
    </div>

    <div class="mt-4">
      <h3>Instructions</h3>

      @if(($needsMoreInstructions ?? false) || !empty($sourceUrl) || !empty($youtubeUrl))
        <div class="note mt-2">
          @if($needsMoreInstructions ?? false)
            <div style="font-weight:900; margin-bottom:8px;">Need more detail?</div>
            <div class="small" style="opacity:.9;">
              This recipe source gives a short method. Use the original source or video below for the fuller cooking flow, and prep all ingredients before starting.
            </div>
          @else
            <div class="small" style="opacity:.9;">More help for this recipe:</div>
          @endif

          @if(!empty($sourceUrl) || !empty($youtubeUrl))
            <div class="mt-3" style="display:flex; gap:10px; flex-wrap:wrap;">
              @if(!empty($sourceUrl))
                <a class="btn btn-secondary" href="{{ $sourceUrl }}" target="_blank" rel="noopener noreferrer">Open full recipe</a>
              @endif

              @if(!empty($youtubeUrl))
                <a class="btn btn-secondary" href="{{ $youtubeUrl }}" target="_blank" rel="noopener noreferrer">Watch video</a>
              @endif
            </div>
          @endif
        </div>
      @endif

      <div class="mt-2" style="white-space:pre-line; opacity:.9;">
        {{ trim((string)$instructions) !== '' ? $instructions : 'No instructions were provided for this recipe.' }}
      </div>

      @if($needsMoreInstructions ?? false)
        <div class="note mt-3">
          <div style="font-weight:900; margin-bottom:8px;">Quick cooking checklist</div>
          <div class="small" style="opacity:.9; line-height:1.55;">
            1. Read the ingredient list and measure everything first.
            2. Start with the longest cooking item, usually potatoes, rice, pasta, meat, or thick vegetables.
            3. Cook aromatics like onion and garlic gently before adding spices, liquids, or sauces.
            4. Add delicate ingredients near the end so they do not overcook.
            5. Taste, adjust seasoning, and make sure meat, eggs, or seafood are fully cooked before serving.
          </div>
        </div>
      @endif
    </div>

  </div>
</div>
@endsection
