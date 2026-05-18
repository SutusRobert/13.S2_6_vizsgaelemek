<?php $__env->startSection('title', ($title ?? 'Recept').' – Receptek'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <h2 style="margin:0;"><?php echo e($title); ?></h2>

        <div class="mt-2">
          <label class="small" style="opacity:.85;">Háztartás</label>
          <form method="get" action="<?php echo e(route('recipes.show', ['id'=>$mealId])); ?>">
            <select name="hid" onchange="this.form.submit()">
              <?php $__currentLoopData = $households; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e((int)$hh['household_id']); ?>" <?php echo e((int)$hh['household_id']===(int)$hid ? 'selected':''); ?>>
                  <?php echo e($hh['name']); ?>

                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </form>
        </div>
      </div>

      <div style="display:flex; gap:10px;">
        <a class="btn btn-secondary" href="<?php echo e(route('recipes.index', ['hid'=>$hid])); ?>">Vissza</a>
        <a class="btn btn-secondary" href="<?php echo e(route('shopping.index', ['hid'=>$hid])); ?>">Bevásárlólista</a>
      </div>
    </div>

    
    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    
    <?php if(($cook ?? '') === 'ok'): ?>
      <div class="success mt-3">✅ Levonva a raktárból. Jó étvágyat!</div>
    <?php endif; ?>
    <?php if(($cook ?? '') === 'err'): ?>
      <div class="error mt-3">❌ <?php echo e($msg ?? 'Hiba történt.'); ?></div>
    <?php endif; ?>

    <?php if(!empty($image)): ?>
      <div class="mt-3">
        <img src="<?php echo e($image); ?>" alt="" style="width:100%; max-height:260px; object-fit:cover; border-radius:16px;">
      </div>
    <?php endif; ?>

    <div class="mt-4">
      <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div>
          <h3 style="margin:0;">Hozzávalók (raktár ellenőrzéssel)</h3>
          <div class="small" style="opacity:.75; margin-top:6px;">
            Hiányzó: <b><?php echo e((int)($missingCount ?? 0)); ?></b> db
          </div>
        </div>

        
        <?php if(((int)($missingCount ?? 0)) === 0): ?>
          <form method="post" action="<?php echo e(route('recipes.consume', ['id'=>$mealId])); ?>" style="margin:0;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="hid" value="<?php echo e((int)$hid); ?>">
            <button type="submit" class="btn btn-primary">🍳 Megcsinálom a kaját (levonás)</button>
          </form>
        <?php else: ?>
          <button type="button" class="btn btn-secondary" disabled>
            🍳 Megcsinálom a kaját (hiányzik <?php echo e((int)($missingCount ?? 0)); ?>)
          </button>
        <?php endif; ?>
      </div>

      <form method="post" action="<?php echo e(route('recipes.missingToShopping', ['id'=>$mealId])); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="hid" value="<?php echo e((int)$hid); ?>">

        <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
          <?php $__currentLoopData = $ingredients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $ing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $has = (bool)($ing['has'] ?? false);

              // ✅ MAGYAR NÉV előnyben, ha van
              $name = (string)($ing['name_hu'] ?? $ing['name'] ?? '');

              $measure = (string)($ing['measure'] ?? '');
            ?>

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
                <div style="font-weight:900;"><?php echo e($name); ?></div>
                <?php if($measure !== ''): ?>
                  <div class="small" style="opacity:.75;"><?php echo e($measure); ?></div>
                <?php endif; ?>
              </div>

              <div style="display:flex; align-items:center; gap:10px;">
                <?php if($has): ?>
                  <span class="badge" style="opacity:.8;">Van</span>
                <?php else: ?>
                  <span class="badge" style="background: rgba(255,80,80,.25); border:1px solid rgba(255,80,80,.35);">Hiányzik</span>

                  
                  <input type="hidden" name="items[<?php echo e($idx); ?>][name]" value="<?php echo e($name); ?>">
                  <input type="hidden" name="items[<?php echo e($idx); ?>][measure]" value="<?php echo e($measure); ?>">

                  <input type="checkbox" checked
                         onchange="
                           const row=this.closest('div');
                           const h1=row.querySelector('input[name=&quot;items[<?php echo e($idx); ?>][name]&quot;]');
                           const h2=row.querySelector('input[name=&quot;items[<?php echo e($idx); ?>][measure]&quot;]');
                           if(!this.checked){ h1.value=''; h2.value=''; } else { h1.value='<?php echo e(addslashes($name)); ?>'; h2.value='<?php echo e(addslashes($measure)); ?>'; }
                         ">
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <button type="submit" class="btn btn-primary mt-3">
          Hiányzók hozzáadása bevásárlólistához
        </button>
      </form>
    </div>

    <div class="mt-4">
      <h3>Elkészítés</h3>
      <div class="mt-2" style="white-space:pre-line; opacity:.9;">
        <?php echo e($instructions); ?>

      </div>
    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\project\MagicFridge\resources\views/recipes/show.blade.php ENDPATH**/ ?>