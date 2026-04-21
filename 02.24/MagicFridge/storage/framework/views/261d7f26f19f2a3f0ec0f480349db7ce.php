<?php $__env->startSection('title','Recipes - MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <h2 style="margin:0;"></h2>
        <div class="small" style="opacity:.8; margin-top:4px;">
           <b><?php echo e($activeHouseholdName ?? ''); ?></b>
        </div>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
      </div>
    </div>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <?php if(is_array($api) && isset($api['_error'])): ?>
    <div style="padding:10px;background:#ff4d4d;color:white;margin-bottom:10px;">
        API ERROR: <?php echo e($api['_error']); ?>

    </div>
    <?php endif; ?>

    
    <div id="own" class="mt-4">
      <h3 style="margin:0;">Own recipes</h3>

      <?php if(empty($own) || count($own) === 0): ?>
        <div class="mt-2" style="opacity:.8;">You don't have a recipes</div>
      <?php else: ?>
        <div class="mt-2" style="display:flex; flex-direction:column; gap:12px;">
          <?php $__currentLoopData = $own; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="display:flex; justify-content:space-between; gap:14px; align-items:stretch; flex-wrap:wrap;
                        border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06);
                        border-radius: 16px; padding: 12px;">
              <div style="display:flex; gap:14px; align-items:center; min-width:260px; flex:1;">
                <?php if(!empty($r->image_path)): ?>
                  <div style="width:150px; height:96px; border-radius:8px; overflow:hidden; background:rgba(0,0,0,.15); flex:0 0 150px;">
                    <img src="<?php echo e(asset($r->image_path)); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                  </div>
                <?php else: ?>
                  <div style="width:150px; height:96px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,.16); color:rgba(255,255,255,.65); flex:0 0 150px; font-weight:800;">
                    No image
                  </div>
                <?php endif; ?>
                <div>
                  <div style="font-weight:900;">
                    <a href="<?php echo e(route('recipes.own.show', ['id' => (int)$r->id, 'hid' => (int)($hid ?? 0)])); ?>"
                       style="text-decoration:none;">
                      <?php echo e($r->title); ?>

                    </a>
                  </div>
                  <div class="small" style="opacity:.75; margin-top:6px;">
                    Saved: <?php echo e($r->created_at); ?>

                  </div>
                </div>
              </div>

              <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <a class="btn btn-secondary"
                   href="<?php echo e(route('recipes.own.show', ['id' => (int)$r->id, 'hid' => (int)($hid ?? 0)])); ?>">
                  Open
                </a>

                <form method="post"
                      action="<?php echo e(route('recipes.own.delete', ['id' => (int)$r->id, 'hid' => (int)($hid ?? 0)])); ?>"
                      style="margin:0;"
                      onsubmit="return confirm('Biztos törlöd?');">
                  <?php echo csrf_field(); ?>
                  <button type="submit" class="btn btn-secondary">Delete</button>
                </form>
              </div>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
      <?php endif; ?>
    </div>

    
    <div class="mt-3" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
      <div style="min-width:260px; flex:1;">
        <label class="small" style="opacity:.85;">Household</label>
        <form method="get" action="<?php echo e(route('recipes.index')); ?>">
          <select name="hid" onchange="this.form.submit()">
            <?php $__currentLoopData = ($households ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $hhId = (int)($hh['household_id'] ?? $hh->household_id ?? 0);
                $hhName = (string)($hh['name'] ?? $hh->name ?? '');
              ?>
              <option value="<?php echo e($hhId); ?>" <?php echo e($hhId === (int)($hid ?? 0) ? 'selected' : ''); ?>>
                <?php echo e($hhName); ?>

              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </form>
      </div>

      <div style="min-width:260px; flex:2;">
        <label class="small" style="opacity:.85;">Search </label>
        <form method="get" action="<?php echo e(route('recipes.index')); ?>" style="display:flex; gap:10px;">
          <input type="hidden" name="hid" value="<?php echo e((int)($hid ?? 0)); ?>">
          <input type="text" name="q" value="<?php echo e((string)($q ?? '')); ?>" placeholder="pl. csirke" style="flex:1;">
          <button type="submit" class="btn btn-primary">Search</button>
        </form>
      </div>

      <div>
       <a class="btn btn-secondary" href="<?php echo e(route('recipes.own.create', ['hid'=> (int)($hid ?? 0)])); ?>">+Add new recipes</a>
      </div>
    </div>

    <hr class="mt-4 mb-3" style="opacity:.25;">

    <h3 style="margin:0;">Recipes </h3>

    <?php
      // Kompatibilitási normalizálás: az új controller $api néven küldi a listát,
      // de régebbi nézetváltozatokban $meals vagy $apiMeals is előfordult.
      $apiMeals = $meals ?? $apiMeals ?? $api ?? [];

      // Ha véletlenül objektum érkezik, tömbként kezeljük tovább.
      if (is_object($apiMeals)) $apiMeals = (array)$apiMeals;

      // Hibaobjektum esetén ne próbáljuk receptlistaként kirajzolni.
      if (is_array($apiMeals) && isset($apiMeals['_error'])) {
        $apiMeals = [];
      }
    ?>

    <?php if(empty($apiMeals)): ?>
      <div class="mt-2" style="opacity:.8;">Not Found.</div>
    <?php else: ?>
      <div class="mt-3" style="
        display:grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap:12px;
      ">
        <?php $__currentLoopData = $apiMeals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            // Több API/controller formátumot is elfogadunk, hogy a kártyák
            // akkor is működjenek, ha idMeal/strMeal vagy id/title érkezik.
            $idMeal = (int)($m['idMeal'] ?? $m->idMeal ?? $m['id'] ?? $m->id ?? 0);

            $nameEn = (string)($m['strMeal'] ?? $m->strMeal ?? $m['title_en'] ?? $m->title_en ?? 'Recipe');

            $thumb  = (string)($m['strMealThumb'] ?? $m->strMealThumb ?? $m['image'] ?? $m->image ?? '');

            $nameHu = (string)($m['name_hu'] ?? $m->name_hu ?? $m['title'] ?? $m->title ?? '');

            $title  = $nameHu !== '' ? $nameHu : $nameEn;
          ?>

          <div class="card" style="padding:12px; border-radius:16px; background: rgba(255,255,255,.06);">
            <?php if($thumb !== ''): ?>
              <div style="border-radius:14px; overflow:hidden; height:120px; background:rgba(0,0,0,.15);">
                <img src="<?php echo e($thumb); ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
              </div>
            <?php else: ?>
              <div style="border-radius:14px; height:120px; background:rgba(0,0,0,.15);"></div>
            <?php endif; ?>

            <div class="mt-2" style="font-weight:900;"><?php echo e($title); ?></div>

            <div class="mt-2">
              <a class="btn btn-secondary"
                 href="<?php echo e(route('recipes.show', ['id'=>$idMeal, 'hid'=>(int)($hid ?? 0)])); ?>">
                Open
              </a>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

      <div class="small mt-2" style="opacity:.65;">
      </div>
    <?php endif; ?>

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/recipes/index.blade.php ENDPATH**/ ?>