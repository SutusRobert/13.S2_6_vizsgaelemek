<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Új recept – MagicFridge</title>
    <link rel="stylesheet" href="assets/style.css?v=1">

    <style>
      .bubbles{ position: fixed; inset: 0; pointer-events: none; z-index: 0; }
      .navbar, .create-row { position: relative; z-index: 2; }

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
      .create-mid .card{ padding: 22px; }
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

      .img-box{
        border:1px solid rgba(255,255,255,.12);
        background: rgba(0,0,0,.10);
        border-radius: 16px;
        overflow:hidden;
      }
      .img-preview{
        width:100%;
        height: 220px;
        object-fit: cover;
        display:block;
      }
      .img-placeholder{
        height: 220px;
        display:flex;
        align-items:center;
        justify-content:center;
        opacity:.8;
        background: linear-gradient(135deg, rgba(255,255,255,.08), rgba(0,0,0,.08));
      }
      .img-actions{
        padding: 12px;
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
        justify-content:space-between;
      }

      textarea{
        width:100%;
        min-height: 180px;
        resize: vertical;
      }

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
            input.required = true;
            input.value = value;
            input.addEventListener('input', updatePreview);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ing-remove';
            btn.title = 'Alapanyag törlése';
            btn.textContent = '✕';
            btn.addEventListener('click', () => {
                const cont = document.getElementById('ingredients');
                row.remove();

                // mindig legyen legalább 1 sor
                const rows = cont.querySelectorAll('.ing-row');
                if (rows.length === 0) {
                    cont.appendChild(createIngredientRow(''));
                }
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

        /* ------------------------------
           Image preview
           ------------------------------ */
        function onImageChange(input){
            const file = input.files && input.files[0] ? input.files[0] : null;
            const img = document.getElementById('imgPreview');
            const ph  = document.getElementById('imgPlaceholder');
            if (!file){
                img.style.display = 'none';
                ph.style.display = 'flex';
                return;
            }
            const ok = /^image\//.test(file.type);
            if (!ok){
                alert('Csak képfájl tölthető fel.');
                input.value = '';
                img.style.display = 'none';
                ph.style.display = 'flex';
                return;
            }
            const url = URL.createObjectURL(file);
            img.src = url;
            img.style.display = 'block';
            ph.style.display = 'none';
        }

        /* ------------------------------
           Live Preview (title + count + list + instructions snippet)
           ------------------------------ */
        function updatePreview(){
            const title = document.getElementById('titleInput')?.value?.trim() || '';
            const servings = document.getElementById('servingsInput')?.value || '';
            const instr = document.getElementById('instructionsInput')?.value?.trim() || '';

            const titleOut = document.getElementById('previewTitle');
            const countOut = document.getElementById('previewCount');
            const warnOut  = document.getElementById('previewWarn');
            const listOut  = document.getElementById('previewList');
            const servOut  = document.getElementById('previewServings');
            const instrOut = document.getElementById('previewInstr');

            const inputs = Array.from(document.querySelectorAll('#ingredients input[name="ingredients[]"]'));
            const vals = inputs.map(i => (i.value || '').trim());
            const cleaned = vals.filter(v => v.length > 0);

            titleOut.textContent = title !== '' ? title : 'Névtelen recept (előnézet)';
            countOut.textContent = cleaned.length.toString();
            servOut.textContent = servings !== '' ? servings : '—';

            const empties = vals.length - cleaned.length;
            warnOut.textContent = empties > 0
                ? ('Figyelj: ' + empties + ' üres hozzávaló sor van.')
                : 'Oké, minden sor töltve.';

            // instructions snippet
            if (instr === ''){
                instrOut.textContent = 'Még nincs elkészítési leírás.';
            } else {
                const short = instr.length > 180 ? instr.slice(0,180) + '…' : instr;
                instrOut.textContent = short;
            }

            listOut.innerHTML = '';
            if (cleaned.length === 0){
                const div = document.createElement('div');
                div.className = 'note';
                div.style.padding = '12px 14px';
                div.textContent = 'Add hozzá az első hozzávalót, és itt azonnal látod a listát.';
                listOut.appendChild(div);
                return;
            }

            cleaned.slice(0, 12).forEach((v, idx) => {
                const row = document.createElement('div');
                row.className = 'preview-item';
                row.innerHTML =
                    '<div><b>' + escapeHtml(v) + '</b><br><small class="muted">#' + (idx+1) + '</small></div>' +
                    '<div class="muted">alapanyag</div>';
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
           30 recept minta (ciklikus, sorban)
           mindegyikhez: title + ingredients + instructions
           ------------------------------ */
        const SAMPLE_RECIPES = [
          { title:"Csirkemell tésztával", ingredients:["Csirkemell","Tészta","Tejszín","Só","Bors"], instructions:
`1) Forralj vizet, sózd meg, főzd ki a tésztát.
2) A csirkét kockázd, pirítsd le kevés olajon.
3) Öntsd rá a tejszínt, fűszerezd sóval-borssal.
4) Keverd össze a tésztával, tálald.` },

          { title:"Bolognai gyorsan", ingredients:["Darált hús","Paradicsomszósz","Tészta","Hagyma","Fokhagyma"], instructions:
`1) Hagymát-fokhagymát piríts.
2) Add hozzá a darált húst, pirítsd morzsásra.
3) Öntsd fel paradicsomszósszal, rotyogtasd 10-15 percig.
4) Közben főzd ki a tésztát, keverd össze.` },

          { title:"Tonhalas tészta", ingredients:["Tonhal konzerv","Tészta","Tejföl","Citrom","Só"], instructions:
`1) Tésztát főzd ki.
2) Tonhalat csöpögtesd le.
3) Keverd össze tejföllel, sóval, pár csepp citrommal.
4) Mehet a tésztára, kész.` },

          { title:"Rántotta sajttal", ingredients:["Tojás","Sajt","Só","Bors","Vaj"], instructions:
`1) Vajat olvassz serpenyőben.
2) Tojást felverd sóval-borssal.
3) Öntsd a serpenyőbe, kevergesd.
4) Reszelt sajt a végén, fedő alatt 1 perc.` },

          { title:"Zöldséges rizs", ingredients:["Rizs","Borsó","Répa","Kukorica","Só"], instructions:
`1) Rizst piríts kevés olajon.
2) Öntsd fel vízzel (kb. 2x), sózd.
3) Add hozzá a zöldségeket.
4) Fedő alatt párold puhára.` },

          // ... (összesen 30)
        ];

        // gyorsan feltöltjük 30-ra úgy, hogy ismétlődjön – de NEM random.
        // Ha kevesebb van, ciklusban duplázunk.
        while (SAMPLE_RECIPES.length < 30){
            const base = SAMPLE_RECIPES[SAMPLE_RECIPES.length % 5];
            SAMPLE_RECIPES.push({
                title: base.title + " (minta " + (SAMPLE_RECIPES.length+1) + ")",
                ingredients: base.ingredients.slice(),
                instructions: base.instructions
            });
        }

        function nextSampleRecipe(){
            const raw = localStorage.getItem('mf_create_recipe_idx');
            let idx = raw ? parseInt(raw, 10) : 0;
            if (!Number.isFinite(idx) || idx < 0) idx = 0;

            const recipe = SAMPLE_RECIPES[idx % SAMPLE_RECIPES.length];
            localStorage.setItem('mf_create_recipe_idx', String((idx + 1) % SAMPLE_RECIPES.length));
            return recipe;
        }

        function quickFill(){
            const recipe = nextSampleRecipe();

            const t = document.getElementById('titleInput');
            if (t) t.value = recipe.title;

            const s = document.getElementById('servingsInput');
            if (s && (s.value === '' || s.value === '0')) s.value = 5;

            const instr = document.getElementById('instructionsInput');
            if (instr) instr.value = recipe.instructions;

            const cont = document.getElementById('ingredients');
            cont.innerHTML = '';
            recipe.ingredients.forEach(v => cont.appendChild(createIngredientRow(v)));
            if (cont.querySelectorAll('.ing-row').length === 0) cont.appendChild(createIngredientRow(''));

            updatePreview();
        }

        function clearAll(){
            document.getElementById('titleInput').value = '';
            document.getElementById('servingsInput').value = 5;
            document.getElementById('instructionsInput').value = '';
            clearIngredients();

            const imgInput = document.getElementById('imageInput');
            imgInput.value = '';
            document.getElementById('imgPreview').style.display = 'none';
            document.getElementById('imgPlaceholder').style.display = 'flex';

            updatePreview();
        }

        window.addEventListener('DOMContentLoaded', () => {
            const cont = document.getElementById('ingredients');
            if (cont && cont.children.length === 0) cont.appendChild(createIngredientRow(''));

            document.getElementById('titleInput')?.addEventListener('input', updatePreview);
            document.getElementById('servingsInput')?.addEventListener('input', updatePreview);
            document.getElementById('instructionsInput')?.addEventListener('input', updatePreview);

            updatePreview();
        });
    </script>
</head>
<body>

<div class="navbar">
    <div class="nav-left">
        <img src="assets/Logo.png" class="nav-logo" alt="Logo">
        <span class="nav-title nav-title--static">MagicFridge</span>
    </div>
    <div class="nav-links">
        <a href="recipes.php">Receptek</a>
        <a href="dashboard.php">Dashboard</a>
    </div>
</div>

<div class="bubbles" aria-hidden="true" id="bubbles">
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span><span></span><span></span><span></span><span></span>
    <span></span><span></span>
</div>

<div class="create-row">

    <!-- BAL PANEL -->
    <div class="create-left">
        <div class="card side-card">
            <div class="side-stack">

                <div class="note">
                    <div style="font-weight:900; margin-bottom:8px;">✨ Gyors tippek</div>
                    <div class="muted">Tölts ki mindent: név, adag, hozzávalók, leírás, kép – ettől lesz “pro recept”.</div>
                </div>

                <div class="note">
                    <div style="font-weight:900; margin-bottom:10px;">⚡ Gyors műveletek</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <button type="button" class="btn btn-mini" onclick="quickFill()">✨ Gyors minta</button>
                        <button type="button" class="btn btn-mini" onclick="clearAll()">🧹 Ürítés</button>
                        <a class="btn btn-mini" href="recipes.php">🍳 Receptek</a>
                        <a class="btn btn-mini" href="dashboard.php">🏠 Dashboard</a>
                    </div>
                </div>

                <div class="note">
                    <div style="font-weight:900; margin-bottom:10px;">📌 Tipp</div>
                    <div class="muted">A leírást írd lépésekben (1), 2), 3)) – az own details szépen meg fogja jeleníteni.</div>
                </div>

            </div>
        </div>
    </div>

    <!-- KÖZÉP: FORM -->
    <div class="create-mid">
        <div class="main-wrapper">
            <div class="card">
                <h2 style="margin-bottom:6px;">Új saját recept</h2>
                <div class="small muted" style="margin-bottom:14px;">
                    Ez már “rendes recept”: kép + adag + elkészítés + hozzávalók.
                </div>

                <form method="post" action="save_recipe.php" enctype="multipart/form-data">
                    <div class="two-col">
                        <div class="form-group">
                            <label for="titleInput">Recept neve</label>
                            <input id="titleInput" type="text" name="title" placeholder="pl. Túrós tészta" required>
                        </div>

                        <div class="form-group">
                            <label for="servingsInput">Adag (fő)</label>
                            <input id="servingsInput" type="number" name="servings" min="1" max="50" value="5" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label>Hozzávalók</label>
                        <div id="ingredients"></div>

                        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                            <button type="button" class="btn btn-secondary" onclick="addIngredient()">+ Új alapanyag</button>
                            <button type="button" class="btn btn-secondary" onclick="quickFill()">✨ Gyors minta</button>
                            <button type="button" class="btn btn-secondary" onclick="clearIngredients()">🧹 Csak alapanyagok ürítése</button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label for="instructionsInput">Elkészítés leírása</label>
                        <textarea id="instructionsInput" name="instructions" placeholder="Írd le lépésenként…"></textarea>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label>Recept képe</label>
                        <div class="img-box">
                            <div id="imgPlaceholder" class="img-placeholder">📷 Nincs kép feltöltve</div>
                            <img id="imgPreview" class="img-preview" style="display:none;" alt="Recept kép előnézet">
                            <div class="img-actions">
                                <input id="imageInput" type="file" name="image" accept="image/*" onchange="onImageChange(this)">
                                <span class="muted">JPG/PNG/WebP ajánlott</span>
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:18px;">
                        <button type="submit" class="btn">Mentés</button>
                        <a href="recipes.php" class="btn btn-secondary">Mégse</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JOBB PANEL: LIVE PREVIEW -->
    <div class="create-right">
        <div class="card side-card">
            <div class="side-stack">

                <div>
                    <div class="preview-title">👀 Élő előnézet</div>
                    <div class="muted" style="margin-bottom:10px;">Amit beírsz, itt azonnal látszik.</div>
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <div class="preview-chip">🧺 Alapanyagok: <b id="previewCount">0</b></div>
                        <div class="preview-chip">👥 Adag: <b id="previewServings">—</b></div>
                    </div>
                </div>

                <div class="note" style="padding:12px 14px;">
                    <div style="font-weight:900; margin-bottom:6px;" id="previewTitle">Névtelen recept (előnézet)</div>
                    <div class="muted" id="previewWarn">Oké, minden sor töltve.</div>
                </div>

                <div id="previewList" class="preview-list"></div>

                <div class="note">
                    <div style="font-weight:900; margin-bottom:8px;">🧾 Leírás előnézet</div>
                    <div class="muted" id="previewInstr">Még nincs elkészítési leírás.</div>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
/* Bubik random indulás */
document.querySelectorAll('#bubbles span').forEach(b => {
  const d = parseFloat(getComputedStyle(b).animationDuration) || 20;
  b.style.animationDelay = (Math.random() * d * -1).toFixed(2) + 's';
});
</script>

</body>
</html>
