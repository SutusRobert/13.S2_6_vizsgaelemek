@extends('layouts.app')
@section('title','Saját recept – MagicFridge')

@section('content')
<div class="main-wrapper">
  <div class="create-row">

    {{-- BAL PANEL --}}
    <div class="create-left">
      <div class="card side-card">
        <div class="side-stack">

          <div class="note">
            <div style="font-weight:900; margin-bottom:8px;">✨ Gyors tippek</div>
            <div class="muted">
              Írj címet + legalább 1 hozzávalót. Ha lépésekben írod a leírást (1), 2), 3)), sokkal szebb lesz a recept.
            </div>
          </div>

          <div class="note">
            <div style="font-weight:900; margin-bottom:10px;">⚡ Gyors műveletek</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
              <button type="button" class="btn btn-mini" onclick="quickFill()">✨ Gyors minta</button>
              <button type="button" class="btn btn-mini" onclick="clearAll()">🧹 Ürítés</button>

              <a class="btn btn-mini" href="{{ route('recipes.index', ['hid' => (int)($hid ?? 0)]) }}">🍳 Receptek</a>
              <a class="btn btn-mini" href="{{ route('dashboard') }}">🏠 Dashboard</a>
            </div>
          </div>

          <div class="note">
            <div style="font-weight:900; margin-bottom:10px;">📌 Tipp</div>
            <div class="muted">
              Ha gyorsan akarsz haladni: “Gyors minta”, utána csak átírod a cím/hozzávalókat.
            </div>
          </div>

          @if($errors->any())
            <div class="error" style="margin-top:8px;">
              {{ $errors->first() }}
            </div>
          @endif

        </div>
      </div>
    </div>

    {{-- KÖZÉP: FORM --}}
    <div class="create-mid">
      <div class="card" style="padding:22px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
          <div>
            <h2 style="margin:0;">Saját recept</h2>
            <div class="small muted" style="margin-top:6px;">
              Töltsd ki és mentsd el — a jobb oldalon azonnal látod az előnézetet.
            </div>
          </div>

          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn btn-secondary" href="{{ route('recipes.index', ['hid' => (int)($hid ?? 0)]) }}">Vissza</a>
          </div>
        </div>

        <form method="post" action="{{ route('recipes.own.store') }}" class="mt-3" onsubmit="return validateIngredients()">
          @csrf
          <input type="hidden" name="hid" value="{{ (int)($hid ?? 0) }}">
                  <div class="mt-3">
        <label class="small" style="opacity:.85;">Elkészítés</label>
        <textarea name="instructions"
                  rows="6"
                  placeholder="Írd le lépésről lépésre az elkészítést..."
                  style="width:100%; resize:vertical;"></textarea>
      </div>

          <div class="two-col">
            <div class="form-group">
              <label for="titleInput">Cím</label>
              <input id="titleInput" type="text" name="title" placeholder="pl. Túrós tészta" value="{{ old('title') }}" required>
            </div>

            <div class="form-group">
              <label class="small" style="opacity:.85;">&nbsp;</label>
              <button type="button" class="btn btn-secondary" style="width:100%;" onclick="quickFill()">✨ Gyors minta</button>
            </div>
          </div>

          <div class="form-group" style="margin-top:14px;">
            <label>Hozzávalók <span class="small muted" style="font-weight:600;">(soronként egy)</span></label>

            <div id="ingredients"></div>
                      @if(!empty($recipe->instructions))
            <div class="mt-4">
              <h3>Elkészítés</h3>
              <div style="white-space: pre-line; opacity:.9;">
                {{ $recipe->instructions }}
              </div>
            </div>
          @endif


            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
              <button type="button" class="btn btn-secondary" onclick="addIngredient()">+ Új hozzávaló</button>
              <button type="button" class="btn btn-secondary" onclick="clearIngredients()">🧹 Hozzávalók ürítése</button>
            </div>

            <div class="small muted" style="margin-top:10px;">
              Tipp: írhatsz mennyiséget is: „1 kg csirkemell”, „2 db tojás”.
            </div>
          </div>

          <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:18px;">
            <button type="submit" class="btn btn-primary">Mentés</button>
            <a href="{{ route('recipes.index', ['hid' => (int)($hid ?? 0)]) }}" class="btn btn-secondary">Mégse</a>
          </div>
        </form>
      </div>
    </div>

    {{-- JOBB PANEL: LIVE PREVIEW --}}
    <div class="create-right">
      <div class="card side-card">
        <div class="side-stack">

          <div>
            <div class="preview-title">👀 Élő előnézet</div>
            <div class="muted" style="margin-bottom:10px;">Amit beírsz, itt azonnal látszik.</div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
              <div class="preview-chip">🧺 Hozzávalók: <b id="previewCount">0</b></div>
              <div class="preview-chip">✅ Kitöltött: <b id="previewFilled">0</b></div>
            </div>
          </div>

          <div class="note" style="padding:12px 14px;">
            <div style="font-weight:900; margin-bottom:6px;" id="previewTitle">Névtelen recept (előnézet)</div>
            <div class="muted" id="previewWarn">Adj hozzá hozzávalókat.</div>
          </div>

          <div id="previewList" class="preview-list"></div>

        </div>
      </div>
    </div>

  </div>
</div>

<style>
  /* layout – ugyanaz a hangulat, mint a régi oldalon, de a te card/btn rendszereden belül */
  .create-row{
    max-width: 1750px;
    margin: 0 auto;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 28px;
    padding: 18px 28px 40px;
    box-sizing: border-box;
  }

  .create-left, .create-right{ width: 420px; flex: 0 0 420px; min-width: 0; }
  .create-mid{ flex: 1 1 auto; min-width: 560px; max-width: 980px; }

  .side-card{ padding: 18px; }
  .side-stack{ display: grid; gap: 14px; }

  .form-group label{ display:block; margin-bottom:6px; font-weight:800; }
  .two-col{
    display:grid;
    grid-template-columns: 1fr 200px;
    gap: 12px;
    align-items:end;
  }

  /* Ingredient rows with remove */
  .ing-row{
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 10px;
  }
  .ing-row input{ flex: 1 1 auto; width: 100%; }

  .ing-remove{
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.9);
    cursor: pointer;
    font-weight: 900;
    line-height: 1;
  }
  .ing-remove:hover{ background: rgba(255,255,255,.10); }

  .preview-title{ font-size: 18px; font-weight: 900; margin-bottom: 8px; }
  .preview-chip{
    display:inline-flex; align-items:center; gap:8px;
    padding:6px 10px; border-radius: 999px;
    border: 1px solid rgba(255,255,255,.14);
    background: rgba(255,255,255,.06);
    font-size: 13px; opacity:.95;
  }
  .preview-list{ margin-top: 12px; display: grid; gap: 8px; }
  .preview-item{
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(0,0,0,.08);
    display:flex;
    justify-content:space-between;
    gap:10px;
  }
  .preview-item small{ opacity:.8; }
  .muted{ opacity:.75; }

  @media (max-width: 1200px){
    .create-row{ flex-direction: column; align-items: center; justify-content: flex-start; max-width: 100%; }
    .create-left, .create-right{ width: min(520px, 100%); flex-basis: auto; }
    .create-mid{ min-width: 0; max-width: 100%; }
    .two-col{ grid-template-columns: 1fr; }
  }
</style>

<script>
  function escapeHtml(str){
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  /* ------------------------------
     Ingredient rows (add/remove)
     ------------------------------ */
  function createIngredientRow(value = '') {
    const row = document.createElement('div');
    row.className = 'ing-row';

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'ingredients[]';
    input.placeholder = 'pl. Csirkemell';
    input.value = value;
    input.addEventListener('input', updatePreview);

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ing-remove';
    btn.title = 'Hozzávaló törlése';
    btn.textContent = '✕';
    btn.addEventListener('click', () => {
      const cont = document.getElementById('ingredients');
      row.remove();

      // mindig legyen legalább 1 sor
      const rows = cont.querySelectorAll('.ing-row');
      if (rows.length === 0) cont.appendChild(createIngredientRow(''));

      updatePreview();
    });

    row.appendChild(input);
    row.appendChild(btn);
    return row;
  }

  function addIngredient(value = '') {
    const cont = document.getElementById('ingredients');
    const row = createIngredientRow(value);
    cont.appendChild(row);
    row.querySelector('input').focus();
    updatePreview();
  }

  function clearIngredients() {
    const cont = document.getElementById('ingredients');
    cont.innerHTML = '';
    cont.appendChild(createIngredientRow(''));
    updatePreview();
  }

  function validateIngredients(){
    const inputs = Array.from(document.querySelectorAll('#ingredients input[name="ingredients[]"]'));
    const cleaned = inputs.map(i => (i.value || '').trim()).filter(v => v.length > 0);
    if (cleaned.length < 1){
      alert('Adj meg legalább 1 hozzávalót.');
      return false;
    }
    return true;
  }

  /* ------------------------------
     Live Preview (title + count + list)
     ------------------------------ */
  function updatePreview(){
    const title = document.getElementById('titleInput')?.value?.trim() || '';

    const titleOut = document.getElementById('previewTitle');
    const countOut = document.getElementById('previewCount');
    const filledOut = document.getElementById('previewFilled');
    const warnOut  = document.getElementById('previewWarn');
    const listOut  = document.getElementById('previewList');

    const inputs = Array.from(document.querySelectorAll('#ingredients input[name="ingredients[]"]'));
    const vals = inputs.map(i => (i.value || '').trim());
    const cleaned = vals.filter(v => v.length > 0);

    titleOut.textContent = title !== '' ? title : 'Névtelen recept (előnézet)';
    countOut.textContent = String(vals.length);
    filledOut.textContent = String(cleaned.length);

    const empties = vals.length - cleaned.length;
    warnOut.textContent = cleaned.length === 0
      ? 'Add hozzá az első hozzávalót.'
      : (empties > 0 ? ('Figyelj: ' + empties + ' üres sor van.') : 'Oké, minden sor töltve.');

    listOut.innerHTML = '';

    if (cleaned.length === 0){
      const div = document.createElement('div');
      div.className = 'note';
      div.style.padding = '12px 14px';
      div.textContent = 'Adj hozzá hozzávalókat, és itt azonnal látod a listát.';
      listOut.appendChild(div);
      return;
    }

    cleaned.slice(0, 12).forEach((v, idx) => {
      const row = document.createElement('div');
      row.className = 'preview-item';
      row.innerHTML =
        '<div><b>' + escapeHtml(v) + '</b><br><small class="muted">#' + (idx+1) + '</small></div>' +
        '<div class="muted">hozzávaló</div>';
      listOut.appendChild(row);
    });

    if (cleaned.length > 12){
      const more = document.createElement('div');
      more.className = 'muted';
      more.style.marginTop = '8px';
      more.textContent = '… és még ' + (cleaned.length - 12) + ' db.';
      listOut.appendChild(more);
    }
  }

  /* ------------------------------
     Quick sample fill (nem random, sorban)
     ------------------------------ */
  const SAMPLE_RECIPES = [
    { title:"Csirkemell tésztával", ingredients:["Csirkemell","Tészta","Tejszín","Só","Bors"] },
    { title:"Bolognai gyorsan", ingredients:["Darált hús","Paradicsomszósz","Tészta","Hagyma","Fokhagyma"] },
    { title:"Tonhalas tészta", ingredients:["Tonhal konzerv","Tészta","Tejföl","Citrom","Só"] },
    { title:"Rántotta sajttal", ingredients:["Tojás","Sajt","Só","Bors","Vaj"] },
    { title:"Zöldséges rizs", ingredients:["Rizs","Borsó","Répa","Kukorica","Só"] },
  ];

  function nextSampleRecipe(){
    const raw = localStorage.getItem('mf_own_create_idx');
    let idx = raw ? parseInt(raw, 10) : 0;
    if (!Number.isFinite(idx) || idx < 0) idx = 0;

    const recipe = SAMPLE_RECIPES[idx % SAMPLE_RECIPES.length];
    localStorage.setItem('mf_own_create_idx', String((idx + 1) % SAMPLE_RECIPES.length));
    return recipe;
  }

  function quickFill(){
    const recipe = nextSampleRecipe();

    const t = document.getElementById('titleInput');
    if (t) t.value = recipe.title;

    const cont = document.getElementById('ingredients');
    cont.innerHTML = '';
    recipe.ingredients.forEach(v => cont.appendChild(createIngredientRow(v)));
    if (cont.querySelectorAll('.ing-row').length === 0) cont.appendChild(createIngredientRow(''));

    updatePreview();
  }

  function clearAll(){
    document.getElementById('titleInput').value = '';
    clearIngredients();
    updatePreview();
  }

  window.addEventListener('DOMContentLoaded', () => {
    const cont = document.getElementById('ingredients');

    // Old input visszatöltés: ha validáció után visszadob, töltsük vissza az old('ingredients')-et, ha van
    const oldIngredients = @json(old('ingredients', []));
    if (Array.isArray(oldIngredients) && oldIngredients.length > 0){
      cont.innerHTML = '';
      oldIngredients.forEach(v => cont.appendChild(createIngredientRow(String(v ?? ''))));
    } else {
      // induljon 6 sorral, hogy ne legyen “csupasz”
      cont.innerHTML = '';
      for (let i=0; i<6; i++) cont.appendChild(createIngredientRow(''));
    }

    document.getElementById('titleInput')?.addEventListener('input', updatePreview);
    updatePreview();
  });
</script>
@endsection
