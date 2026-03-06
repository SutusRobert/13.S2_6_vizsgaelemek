@extends('layouts.app')
@section('title', ($title ?? 'Recept').' – Receptek')

@section('content')
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <h2 style="margin:0;">{{ $title }}</h2>

        <div class="mt-2">
          <label class="small" style="opacity:.85;">Háztartás</label>
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
        <a class="btn btn-secondary" href="{{ route('recipes.index', ['hid'=>$hid]) }}">Vissza</a>
        <a class="btn btn-secondary" href="{{ route('shopping.index', ['hid'=>$hid]) }}">Bevásárlólista</a>
      </div>
    </div>

    {{-- Siker/hiba üzenetek --}}
    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    {{-- Főzés (levonás) eredménye --}}
    @if(($cook ?? '') === 'ok')
      <div class="success mt-3">✅ Levonva a raktárból. Jó étvágyat!</div>
    @endif
    @if(($cook ?? '') === 'err')
      <div class="error mt-3">❌ {{ $msg ?? 'Hiba történt.' }}</div>
    @endif

    @if(!empty($image))
      <div class="mt-3">
        <img src="{{ $image }}" alt="" style="width:100%; max-height:260px; object-fit:cover; border-radius:16px;">
      </div>
    @endif

    <div class="mt-4">
      <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div>
          <h3 style="margin:0;">Hozzávalók (raktár ellenőrzéssel)</h3>
          <div class="small" style="opacity:.75; margin-top:6px;">
            Hiányzó: <b>{{ (int)($missingCount ?? 0) }}</b> db
          </div>
        </div>

        {{-- 🍳 Megcsinálom (levonás) --}}
        @if(((int)($missingCount ?? 0)) === 0)
          <form method="post" action="{{ route('recipes.consume', ['id'=>$mealId]) }}" style="margin:0;">
            @csrf
            <input type="hidden" name="hid" value="{{ (int)$hid }}">
            <button type="submit" class="btn btn-primary">🍳 Megcsinálom a kaját (levonás)</button>
          </form>
        @else
          <button type="button" class="btn btn-secondary" disabled>
            🍳 Megcsinálom a kaját (hiányzik {{ (int)($missingCount ?? 0) }})
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

              // ✅ MAGYAR NÉV előnyben, ha van
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
                  <span class="badge" style="opacity:.8;">Van</span>
                @else
                  <span class="badge" style="background: rgba(255,80,80,.25); border:1px solid rgba(255,80,80,.35);">Hiányzik</span>

                  {{-- Bevásárlólistára már a magyar nevet küldjük --}}
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
          Hiányzók hozzáadása bevásárlólistához
        </button>
      </form>
    </div>

    <div class="mt-4">
      <h3>Elkészítés</h3>
      <div class="mt-2" style="white-space:pre-line; opacity:.9;">
        {{ $instructions }}
      </div>
    </div>

  </div>
</div>
@endsection