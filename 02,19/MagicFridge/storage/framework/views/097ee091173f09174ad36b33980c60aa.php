<?php $__env->startSection('title', ($recipe->title ?? 'Saját recept') . ' – MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%; padding:22px;">

    
    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <div class="small" style="opacity:.75; margin-bottom:6px;">Saját recept részletei</div>
        <h2 style="margin:0;"><?php echo e($recipe->title ?? 'Saját recept'); ?></h2>

        <?php if(!empty($recipe->created_at)): ?>
          <div class="small" style="opacity:.75; margin-top:8px;">
            Mentve: <?php echo e($recipe->created_at); ?>

          </div>
        <?php endif; ?>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="<?php echo e(route('recipes.index', ['hid' => (int)($hid ?? request()->get('hid', 0))])); ?>">🍽️ Saját receptek</a>

        <a class="btn btn-secondary" href="<?php echo e(url()->previous()); ?>">⬅️ Vissza</a>
      </div>
    </div>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    
    <div class="mt-4" style="display:grid; grid-template-columns: 1fr 320px; gap:18px;">
      
      <div style="border:1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.08); border-radius:18px; padding:16px;">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
          <h3 style="margin:0;">🧺 Hozzávalók</h3>
          <div class="small" style="opacity:.8;">
            Összesen: <b><?php echo e(is_countable($ingredients ?? null) ? count($ingredients) : 0); ?></b>
          </div>
        </div>

        <?php if(empty($ingredients) || count($ingredients) === 0): ?>
          <div class="small mt-2" style="opacity:.8;">Nincs hozzávaló.</div>
        <?php else: ?>
          <div class="mt-3" style="display:flex; flex-wrap:wrap; gap:10px;">
            <?php $__currentLoopData = $ingredients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <div style="
                padding:10px 12px;
                border-radius:999px;
                border:1px solid rgba(255,255,255,.14);
                background: rgba(255,255,255,.06);
                font-weight:700;
                ">
                <?php echo e($ing->ingredient ?? ''); ?>

              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        <?php endif; ?>
      </div>

              <?php if(!empty($recipe->instructions)): ?>
          <div class="mt-4">
            <h3>Elkészítés</h3>
            <div style="white-space: pre-line; opacity:.9;">
              <?php echo e($recipe->instructions); ?>

            </div>
          </div>
        <?php endif; ?>


      
      <div style="border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); border-radius:18px; padding:16px;">
        <div style="font-weight:900; margin-bottom:10px;">⚡ Gyors műveletek</div>

        <div style="display:grid; gap:10px;">
            <a class="btn btn-primary" href="<?php echo e(route('recipes.index', ['hid' => (int)($hid ?? request()->get('hid', 0))])); ?>">🍽️ Saját receptek</a>

            <a class="btn btn-secondary" href="<?php echo e(route('recipes.own.create', ['hid' => (int)($hid ?? request()->get('hid', 0))])); ?>">+ Új saját recept</a>

          <a class="btn btn-secondary" href="<?php echo e(route('dashboard')); ?>">🏠 Dashboard</a>
        </div>

        <div class="small" style="opacity:.75; margin-top:12px; line-height:1.5;">
          Tipp: ha szeretnél később “leírást / lépéseket” is a saját recepthez, tudunk hozzá mezőt és DB oszlopot adni.
        </div>
      </div>
    </div>

  </div>
</div>

<style>
  @media (max-width: 980px){
    .card > div.mt-4 { grid-template-columns: 1fr !important; }
  }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\project\MagicFridge\resources\views/recipes/own_show.blade.php ENDPATH**/ ?>