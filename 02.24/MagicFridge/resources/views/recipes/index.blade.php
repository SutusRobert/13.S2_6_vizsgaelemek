@extends('layouts.app')
@section('title','Recipes - MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <h2 style="margin:0;"></h2>
        <div class="small" style="opacity:.8; margin-top:4px;">
           <b>{{ $activeHouseholdName ?? '' }}</b>
        </div>
      </div>
      

      


  
      </a>


      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        
      </div>
    </div>
    
    @if(session('success'))
      <div class="success mt-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="error mt-3">{{ $errors->first() }}</div>
    @endif

    @if(is_array($api) && isset($api['_error']))
    <div style="padding:10px;background:#ff4d4d;color:white;margin-bottom:10px;">
        API ERROR: {{ $api['_error'] }}
    </div>
    @endif


    {{-- My custom recipes --}}
<div id="own" class="mt-4">
  <h3 style="margin:0;">Own recipes</h3>

  @if(empty($own) || count($own) === 0)
    <div class="mt-2" style="opacity:.8;">You don't have a recipes</div>
  @else
    <div class="mt-2" style="display:flex; flex-direction:column; gap:10px;">
      @foreach($own as $r)
        <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;
                    border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06);
                    border-radius: 16px; padding: 10px 12px;">
          <div>
            <div style="font-weight:900;">
              <a href="{{ route('recipes.own.show', ['id' => (int)$r->id, 'hid' => (int)($hid ?? 0)]) }}"
                 style="text-decoration:none;">
                {{ $r->title }}
              </a>
            </div>
            <div class="small" style="opacity:.75;">
              Saved: {{ $r->created_at }}
            </div>
          </div>

          <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn btn-secondary"
               href="{{ route('recipes.own.show', ['id' => (int)$r->id, 'hid' => (int)($hid ?? 0)]) }}">
              Open
            </a>

            <form method="post"
                  action="{{ route('recipes.own.delete', ['id' => (int)$r->id, 'hid' => (int)($hid ?? 0)]) }}"
                  style="margin:0;"
                  onsubmit="return confirm('Biztos tĂ¶rlĂ¶d?');">
              @csrf
              <button type="submit" class="btn btn-secondary">Delete</button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>


    {{-- Top row: household + search --}}
    <div class="mt-3" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
      <div style="min-width:260px; flex:1;">
        <label class="small" style="opacity:.85;">Household</label>
        <form method="get" action="{{ route('recipes.index') }}">
          <select name="hid" onchange="this.form.submit()">
            @foreach(($households ?? []) as $hh)
              @php
                $hhId = (int)($hh['household_id'] ?? $hh->household_id ?? 0);
                $hhName = (string)($hh['name'] ?? $hh->name ?? '');
              @endphp
              <option value="{{ $hhId }}" {{ $hhId === (int)($hid ?? 0) ? 'selected' : '' }}>
                {{ $hhName }}
              </option>
            @endforeach
          </select>
        </form>
      </div>

      <div style="min-width:260px; flex:2;">
        <label class="small" style="opacity:.85;">Search </label>
        <form method="get" action="{{ route('recipes.index') }}" style="display:flex; gap:10px;">
          <input type="hidden" name="hid" value="{{ (int)($hid ?? 0) }}">
          <input type="text" name="q" value="{{ (string)($q ?? '') }}" placeholder="pl. csirke" style="flex:1;">
          <button type="submit" class="btn btn-primary">Search</button>
        </form>
      </div>

      <div>
       <a class="btn btn-secondary" href="{{ route('recipes.own.create', ['hid'=> (int)($hid ?? 0)]) }}">+Add new recipes</a>
        
      </div>
    </div>

    <hr class="mt-4 mb-3" style="opacity:.25;">

   
    <h3 style="margin:0;">Recipes </h3>

    @php
      // JAVĂŤTĂS:
      // Controller already sends the list in $api (id, title, image)
      // Previous names: $meals / $apiMeals
      // Normalize for compatibility:
      $apiMeals = $meals ?? $apiMeals ?? $api ?? [];

      // if an object arrives by mistake
      if (is_object($apiMeals)) $apiMeals = (array)$apiMeals;

      // if an _error array arrives, do not treat it as results
      if (is_array($apiMeals) && isset($apiMeals['_error'])) {
        $apiMeals = [];
      }
    @endphp

    @if(empty($apiMeals))
      <div class="mt-2" style="opacity:.8;">Not Found.</div>
    @else
      <div class="mt-3" style="
        display:grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap:12px;
      ">
        @foreach($apiMeals as $m)
          @php
            // JAVĂŤTĂS:
            // Support newer controller structure as well:
            // id, title, image
            $idMeal = (int)($m['idMeal'] ?? $m->idMeal ?? $m['id'] ?? $m->id ?? 0);

            $nameEn = (string)($m['strMeal'] ?? $m->strMeal ?? $m['title_en'] ?? $m->title_en ?? 'Recipe');

            $thumb  = (string)($m['strMealThumb'] ?? $m->strMealThumb ?? $m['image'] ?? $m->image ?? '');

            $nameHu = (string)($m['name_hu'] ?? $m->name_hu ?? $m['title'] ?? $m->title ?? '');

            $title  = $nameHu !== '' ? $nameHu : $nameEn;
          @endphp

          <div class="card" style="padding:12px; border-radius:16px; background: rgba(255,255,255,.06);">
            @if($thumb !== '')
              <div style="border-radius:14px; overflow:hidden; height:120px; background:rgba(0,0,0,.15);">
                <img src="{{ $thumb }}" alt="" style="width:100%; height:100%; object-fit:cover;">
              </div>
            @else
              <div style="border-radius:14px; height:120px; background:rgba(0,0,0,.15);"></div>
            @endif

            <div class="mt-2" style="font-weight:900;">{{ $title }}</div>

            <div class="mt-2">
              <a class="btn btn-secondary"
                 href="{{ route('recipes.show', ['id'=>$idMeal, 'hid'=>(int)($hid ?? 0)]) }}">
                Open
              </a>
            </div>
          </div>
        @endforeach
      </div>

      <div class="small mt-2" style="opacity:.65;">
      </div>
    @endif

  </div>
</div>

<style>
@media (max-width: 920px){
  .main-wrapper .card > div[style*="grid-template-columns"]{
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }
}
@media (max-width: 620px){
  .main-wrapper .card > div[style*="grid-template-columns"]{
    grid-template-columns: 1fr !important;
  }
}
</style>
@endsection

