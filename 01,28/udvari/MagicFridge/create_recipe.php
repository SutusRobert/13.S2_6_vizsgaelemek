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
      .bubbles{
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 0;
      }
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

      .create-left, .create-right{
        width: 420px;
        flex: 0 0 420px;
        min-width: 0;
      }

      .create-mid{
        flex: 1 1 auto;
        min-width: 560px;
        max-width: 980px;
      }

      .create-mid .card{ padding: 22px; }
      .side-card{ padding: 18px; }
      .side-stack{ display: grid; gap: 14px; }

      .form-group label{ display:block; margin-bottom:6px; font-weight:800; }

      /* Ingredients rows with remove */
      .ing-row{
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 10px;
      }
      .ing-row input{
        flex: 1 1 auto;
        width: 100%;
      }
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
      .ing-remove:hover{
        background: rgba(255,255,255,.10);
      }

      .preview-title{
        font-size: 18px;
        font-weight: 900;
        margin-bottom: 8px;
      }
      .preview-chip{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:6px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,.14);
        background: rgba(255,255,255,.06);
        font-size: 13px;
        opacity:.95;
      }
      .preview-list{
        margin-top: 12px;
        display: grid;
        gap: 8px;
      }
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
        .create-row{
          flex-direction: column;
          align-items: center;
          justify-content: flex-start;
          max-width: 100%;
        }
        .create-left, .create-right{ width: min(520px, 100%); flex-basis: auto; }
        .create-mid{ min-width: 0; max-width: 100%; }
      }
    </style>

    <script>
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
           Live Preview
           ------------------------------ */
        function updatePreview(){
            const title = document.getElementById('titleInput')?.value?.trim() || '';
            const titleOut = document.getElementById('previewTitle');
            const countOut = document.getElementById('previewCount');
            const listOut  = document.getElementById('previewList');
            const warnOut  = document.getElementById('previewWarn');

            const inputs = Array.from(document.querySelectorAll('#ingredients input[name="ingredients[]"]'));
            const vals = inputs.map(i => (i.value || '').trim());
            const cleaned = vals.filter(v => v.length > 0);

            titleOut.textContent = title !== '' ? title : 'Névtelen recept (előnézet)';
            countOut.textContent = cleaned.length.toString();

            const empties = vals.length - cleaned.length;
            warnOut.textContent = empties > 0
                ? ('Figyelj: ' + empties + ' üres hozzávaló sor van.')
                : 'Oké, minden sor töltve.';

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

        function escapeHtml(str){
            return String(str)
              .replaceAll('&','&amp;')
              .replaceAll('<','&lt;')
              .replaceAll('>','&gt;')
              .replaceAll('"','&quot;')
              .replaceAll("'","&#039;");
        }

        /* ------------------------------
           30+ Tips + 30+ Mini (cycle)
           ------------------------------ */
        const TIP_LIST = [
          "Írj alapanyagokat bolti néven: „Kristálycukor”, „Tej 2,8%”.",
          "A hozzávalók elé írj mennyiséget is: „200 g liszt”.",
          "Egységként használd: g / kg / ml / l / db – könnyebb levonni.",
          "Ha sokszor főzöd, legyen benne „alap” fűszerlista (só, bors, paprika).",
          "A „hamar lejár” cuccokból csinálj receptet elsőnek.",
          "Liszt/cukor/rizs: tipikusan 1 kg-os kiszerelés.",
          "Tej: tipikusan 1 liter.",
          "Tojás: 6-os tálca reális (ha 1–2 kell is).",
          "A recept neve legyen konkrét: „Csirkepaprikás nokedlivel”.",
          "Írd le az alapot: fehérje + köret + szósz.",
          "Legyen 1 „gyors” verzió (15-20 perc).",
          "Maradékbarát recept = kevesebb pazarlás.",
          "Vajnál a gramm a barátod (10 g, 20 g).",
          "Tejföl: reális a „1 doboz” vásárlás.",
          "Joghurt: reális a „1 pohár” vásárlás.",
          "Olaj: reális az „1 üveg”, nem 2 kanál.",
          "Ecet: reális az „1 üveg”, nem 10 ml.",
          "Fűszereknél egyszerűsíts: kicsi fogyás / recept.",
          "Legyen 1 „vész” recept (tojás + kenyér + sajt).",
          "Fagyasztó trükk: csinálj 1 adaggal többet.",
          "Hagyma/fokhagyma: jöhet db-ban.",
          "Paprika/paradicsom: inkább db.",
          "Tészta: 500 g/csomag gondolkodás.",
          "Rizs: 1 kg vagy 500 g.",
          "Sajt: 200–300 g/csomag.",
          "Sonka: 1 csomag reális.",
          "Csirke: 500 g/csomag logika.",
          "Levesnél jelöld a víz mennyiséget (pl. 2 l).",
          "Mindig legyen 1 „olcsó” recept (bab/lencse/tészta).",
          "Kezdésnek 5-7 alapanyag elég: működjön, ne legyen túl nagy."
        ];

        const MINI_LIST = [
          "Mini: legyen benne a fő alapanyag a névben.",
          "Mini: zöldség → db-ban (2 paradicsom).",
          "Mini: hús → grammban (500 g csirke).",
          "Mini: tészta/rizs → csomagban.",
          "Mini: cukor/liszt → 1 kg reális.",
          "Mini: tej → 1 l reális.",
          "Mini: vaj → 1 csomag (200 g) reális.",
          "Mini: sajt → 1 csomag (200 g) reális.",
          "Mini: só/bors legyen mindig raktáron.",
          "Mini: tejszín helyett opció: tejföl.",
          "Mini: írd bele a “maradékot” is később.",
          "Mini: gyorsabb keresés: rövid, konkrét nevek.",
          "Mini: „hús” helyett „csirke”.",
          "Mini: mirelit cuccokat jelöld külön.",
          "Mini: leveshez víz mennyiséget jelöld.",
          "Mini: desszert: cukor/liszt/tej alap.",
          "Mini: heti 1 készletből recept.",
          "Mini: 1 recept = 1 fő logika.",
          "Mini: paprika: őrölt vs friss.",
          "Mini: paradicsom: konzerv vs friss.",
          "Mini: tejszín: főző vs hab.",
          "Mini: sajt: trappista vs mozzarella.",
          "Mini: joghurt: natúr vs görög.",
          "Mini: rizs: jázmin/basmati.",
          "Mini: tészta: penne/spaghetti.",
          "Mini: csirke: mell/comb.",
          "Mini: fűszer: szárított könnyebb.",
          "Mini: adj 1 mondat tippet (idő).",
          "Mini: 20+ alapanyag → bontsd ketté.",
          "Mini: ha bizonytalan, kezdd egyszerűen."
        ];

        function cycleText(storageKey, list){
            const raw = localStorage.getItem(storageKey);
            let idx = raw ? parseInt(raw, 10) : 0;
            if (!Number.isFinite(idx) || idx < 0) idx = 0;
            const text = list[idx % list.length];
            localStorage.setItem(storageKey, String((idx + 1) % list.length));
            return text;
        }

        /* ------------------------------
           30 Recipes (cycle, not random)
           ------------------------------ */
        const SAMPLE_RECIPES = [
          { title:"Csirkemell tésztával", ingredients:["Csirkemell", "Tészta", "Tejszín", "Só", "Bors"] },
          { title:"Bolognai gyorsan", ingredients:["Darált hús", "Paradicsomszósz", "Tészta", "Hagyma", "Fokhagyma"] },
          { title:"Tonhalas tészta", ingredients:["Tonhal konzerv", "Tészta", "Tejföl", "Citrom", "Só"] },
          { title:"Rántotta sajttal", ingredients:["Tojás", "Sajt", "Só", "Bors", "Vaj"] },
          { title:"Zöldséges rizs", ingredients:["Rizs", "Borsó", "Répa", "Kukorica", "Só"] },
          { title:"Tejfölös csirkepaprikás", ingredients:["Csirkecomb", "Hagyma", "Tejföl", "Paprika", "Só"] },
          { title:"Lencsefőzelék", ingredients:["Lencse", "Hagyma", "Fokhagyma", "Babérlevél", "Tejföl"] },
          { title:"Paradicsomleves", ingredients:["Paradicsom konzerv", "Víz", "Cukor", "Só", "Tészta"] },
          { title:"Sajtos melegszendvics", ingredients:["Kenyér", "Sonka", "Sajt", "Vaj", "Oregánó"] },
          { title:"Görög saláta", ingredients:["Uborka", "Paradicsom", "Feta sajt", "Olívaolaj", "Só"] },

          { title:"Túrós csusza", ingredients:["Tészta", "Túró", "Tejföl", "Szalonna", "Só"] },
          { title:"Pankó rántott csirke", ingredients:["Csirkemell", "Tojás", "Zsemlemorzsa", "Liszt", "Olaj"] },
          { title:"Carbonara alap", ingredients:["Tészta", "Tojás", "Szalonna", "Parmezán", "Bors"] },
          { title:"Chilis bab", ingredients:["Bab konzerv", "Darált hús", "Paradicsom", "Chili", "Hagyma"] },
          { title:"Krumplipüré + fasírt", ingredients:["Burgonya", "Vaj", "Tej", "Darált hús", "Zsemlemorzsa"] },
          { title:"Sütőben sült zöldség", ingredients:["Cukkini", "Padlizsán", "Paprika", "Olívaolaj", "Só"] },
          { title:"Gombapaprikás", ingredients:["Gomba", "Hagyma", "Tejföl", "Paprika", "Só"] },
          { title:"Tuna saláta", ingredients:["Tonhal konzerv", "Kukorica", "Joghurt", "Citrom", "Só"] },
          { title:"Rizottó alap", ingredients:["Rizs", "Hagyma", "Vaj", "Alaplé", "Parmezán"] },
          { title:"Fokhagymás-tejszínes csirke", ingredients:["Csirkemell", "Fokhagyma", "Tejszín", "Só", "Bors"] },

          { title:"Palacsinta", ingredients:["Liszt", "Tej", "Tojás", "Cukor", "Olaj"] },
          { title:"Bundás kenyér", ingredients:["Kenyér", "Tojás", "Tej", "Só", "Olaj"] },
          { title:"Zabkása", ingredients:["Zabpehely", "Tej", "Méz", "Fahéj", "Gyümölcs"] },
          { title:"Gyümölcssaláta", ingredients:["Banán", "Alma", "Narancs", "Citrom", "Méz"] },
          { title:"Pesto tészta", ingredients:["Tészta", "Pesto", "Parmezán", "Olívaolaj", "Só"] },
          { title:"Tavaszi omlett", ingredients:["Tojás", "Sonka", "Sajt", "Paprika", "Só"] },
          { title:"Húsleves egyszerűen", ingredients:["Csirke", "Répa", "Petrezselyem", "Víz", "Só"] },
          { title:"Sült rizs tojással", ingredients:["Rizs", "Tojás", "Szójaszósz", "Borsó", "Hagyma"] },
          { title:"Tésztasaláta", ingredients:["Tészta", "Uborka", "Paradicsom", "Joghurt", "Só"] },
          { title:"Kakaó", ingredients:["Tej", "Kakaópor", "Cukor", "Fahéj", "Tejszín"] }
        ];

        function nextSampleRecipe(){
            const raw = localStorage.getItem('mf_create_recipe_idx');
            let idx = raw ? parseInt(raw, 10) : 0;
            if (!Number.isFinite(idx) || idx < 0) idx = 0;

            const recipe = SAMPLE_RECIPES[idx % SAMPLE_RECIPES.length];
            localStorage.setItem('mf_create_recipe_idx', String((idx + 1) % SAMPLE_RECIPES.length));
            return recipe;
        }

        function quickFill(){
            // mindig generálható: felülírjuk a formot a következő mintával
            const recipe = nextSampleRecipe();

            const t = document.getElementById('titleInput');
            if (t) t.value = recipe.title;

            const cont = document.getElementById('ingredients');
            cont.innerHTML = '';
            recipe.ingredients.forEach(v => cont.appendChild(createIngredientRow(v)));

            // ha valamiért üres lenne, legyen 1 sor
            if (cont.querySelectorAll('.ing-row').length === 0) {
                cont.appendChild(createIngredientRow(''));
            }

            updatePreview();
        }

        window.addEventListener('DOMContentLoaded', () => {
            // init ingredient container with 1 row (soha nem üres)
            const cont = document.getElementById('ingredients');
            if (cont && cont.children.length === 0) cont.appendChild(createIngredientRow(''));

            const t = document.getElementById('titleInput');
            if (t) t.addEventListener('input', updatePreview);

            // ciklikus tippek
            const tipEl = document.getElementById('leftTip');
            const miniEl = document.getElementById('rightMini');
            if (tipEl) tipEl.textContent = cycleText('mf_create_tip_idx', TIP_LIST);
            if (miniEl) miniEl.textContent = cycleText('mf_create_mini_idx', MINI_LIST);

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
                    <div style="font-weight:900; margin-bottom:8px;">✨ Napi tipp</div>
                    <div id="leftTip">…</div>
                </div>

                <div class="note">
                    <div style="font-weight:900; margin-bottom:10px;">📏 Mérték cheat sheet</div>
                    <div class="muted" style="display:grid; gap:6px;">
                        <div>• 1 tsp (kiskanál) ≈ 5 ml</div>
                        <div>• 1 tbsp (evőkanál) ≈ 15 ml</div>
                        <div>• 1 cup ≈ 240 ml</div>
                        <div>• Cukor: 1 tsp ≈ 4 g</div>
                        <div>• Liszt: 1 cup ≈ 120 g</div>
                    </div>
                </div>

                <div class="note">
                    <div style="font-weight:900; margin-bottom:10px;">⚡ Gyors műveletek</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <a class="btn btn-mini" href="inventory.php">🧊 Raktár</a>
                        <a class="btn btn-mini" href="shopping_list.php">🛒 Lista</a>
                        <a class="btn btn-mini" href="recipes.php">🍳 Receptek</a>
                        <button type="button" class="btn btn-mini" onclick="quickFill()">✨ Gyors minta</button>
                    </div>
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
                    Mentés után megjelenik a receptek között. A “Gyors minta” 30 receptet teker sorban.
                </div>

                <form method="post" action="save_recipe.php">
                    <div class="form-group">
                        <label for="titleInput">Recept neve</label>
                        <input id="titleInput" type="text" name="title" placeholder="pl. Csirkemell tésztával" required>
                    </div>

                    <div class="form-group" style="margin-top:14px;">
                        <label>Alapanyagok</label>

                        <div id="ingredients"></div>

                        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
                            <button type="button" class="btn btn-secondary" onclick="addIngredient()">+ Új alapanyag</button>
                            <button type="button" class="btn btn-secondary" onclick="quickFill()">✨ Gyors minta</button>
                            <button type="button" class="btn btn-secondary" onclick="clearIngredients()">🧹 Ürítés</button>
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

    <!-- JOBB PANEL -->
    <div class="create-right">
        <div class="card side-card">
            <div class="side-stack">

                <div>
                    <div class="preview-title">👀 Élő előnézet</div>
                    <div class="muted" style="margin-bottom:10px;">Amit beírsz, itt azonnal listázódik.</div>
                    <div class="preview-chip">🧺 Alapanyagok: <b id="previewCount">0</b></div>
                </div>

                <div class="note" style="padding:12px 14px;">
                    <div style="font-weight:900; margin-bottom:6px;" id="previewTitle">Névtelen recept (előnézet)</div>
                    <div class="muted" id="previewWarn">Oké, minden sor töltve.</div>
                </div>

                <div id="previewList" class="preview-list"></div>

                <div class="note">
                    <div style="font-weight:900; margin-bottom:8px;">💡 Mini okosság</div>
                    <div class="muted" id="rightMini">…</div>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
/* Bubik random indulás + parallax */
(() => {
  const bubbles = document.getElementById('bubbles');
  if (!bubbles) return;

  const items = Array.from(bubbles.querySelectorAll('span')).map((el, i) => {
    const dur = parseFloat(getComputedStyle(el).animationDuration) || 20;
    el.style.animationDelay = (Math.random() * dur * -1).toFixed(2) + 's';
    const speed = 0.6 + (i % 7) * 0.15;
    const depth = 8 + (i % 6) * 6;
    return { el, speed, depth };
  });

  let mx = 0, my = 0, tx = 0, ty = 0;
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  window.addEventListener('mousemove', (e) => {
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    mx = clamp((e.clientX - cx) / cx, -1, 1);
    my = clamp((e.clientY - cy) / cy, -1, 1);
  }, { passive: true });

  function tick() {
    tx += (mx - tx) * 0.06;
    ty += (my - ty) * 0.06;

    const sy = window.scrollY || 0;
    for (const it of items) {
      const px = tx * it.depth * it.speed;
      const py = ty * it.depth * it.speed + (sy * 0.02 * it.speed);
      it.el.style.transform = `translate3d(${px.toFixed(2)}px, ${py.toFixed(2)}px, 0)`;
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
</script>

</body>
</html>
