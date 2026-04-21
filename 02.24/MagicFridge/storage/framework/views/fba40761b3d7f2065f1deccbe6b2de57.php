<?php $__env->startSection('title', ($recipe->title ?? 'Custom recipe') . ' - MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%; padding:22px;">

    
    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start;">
      <div>
        <div class="small" style="opacity:.75; margin-bottom:6px;">Custom recipe details</div>
        <h2 style="margin:0;"><?php echo e($recipe->title ?? 'Own recipes'); ?></h2>

        <?php if(!empty($recipe->created_at)): ?>
          <div class="small" style="opacity:.75; margin-top:8px;">
            Saved: <?php echo e($recipe->created_at); ?>

          </div>
        <?php endif; ?>

        <div class="mt-2">
          <label class="small" style="opacity:.85;">Household</label>
          <form method="get" action="<?php echo e(route('recipes.own.show', ['id' => (int)$recipe->id])); ?>">
            <select name="hid" onchange="this.form.submit()">
              <?php $__currentLoopData = ($households ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e((int)$hh['household_id']); ?>" <?php echo e((int)$hh['household_id'] === (int)$hid ? 'selected' : ''); ?>>
                  <?php echo e($hh['name']); ?>

                </option>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
          </form>
        </div>
      </div>

      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-secondary" href="<?php echo e(route('recipes.index', ['hid' => (int)$hid])); ?>">&larr; Recipes</a>
        <a class="btn btn-secondary" href="<?php echo e(route('shopping.index', ['hid' => (int)$hid])); ?>">Shopping list</a>
      </div>
    </div>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <?php if(($cook ?? '') === 'ok'): ?>
      <div class="success mt-3">Deducted from stock. Enjoy your meal!</div>
    <?php endif; ?>
    <?php if(($cook ?? '') === 'err'): ?>
      <div class="error mt-3"><?php echo e($msg ?? 'Error occurred.'); ?></div>
    <?php endif; ?>

    <div class="mt-3" style="width:100%; min-height:260px; border-radius:16px; overflow:hidden; background:rgba(0,0,0,.16); border:1px solid rgba(255,255,255,.12); display:flex; align-items:center; justify-content:center;">
      <?php if(!empty($recipe->image_path)): ?>
        <img src="<?php echo e(asset($recipe->image_path)); ?>" alt="" style="width:100%; height:300px; object-fit:cover;">
      <?php else: ?>
        <div style="opacity:.7; font-weight:900;">No image for this recipe.</div>
      <?php endif; ?>
      </div>

    <form method="post" action="<?php echo e(route('recipes.own.image', ['id' => (int)$recipe->id])); ?>" enctype="multipart/form-data" class="mt-3" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="hid" value="<?php echo e((int)$hid); ?>">
      <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" required style="max-width:320px;">
      <button type="submit" class="btn btn-secondary">
        <?php echo e(!empty($recipe->image_path) ? 'Change image' : 'Add image'); ?>

      </button>
    </form>

    
    <div class="mt-4" style="display:grid; grid-template-columns: 1fr 320px; gap:18px;">
      <div style="border:1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.08); border-radius:18px; padding:16px;">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
          <div>
            <h3 style="margin:0;">Ingredients (with stock check)</h3>
            <div class="small" style="opacity:.75; margin-top:6px;">
              Missing: <b><?php echo e((int)($missingCount ?? 0)); ?></b> db
            </div>
          </div>

          <?php if(((int)($missingCount ?? 0)) === 0 && !empty($ingredients)): ?>
            <form method="post" action="<?php echo e(route('recipes.own.consume', ['id' => (int)$recipe->id])); ?>" style="margin:0;">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="hid" value="<?php echo e((int)$hid); ?>">
              <button type="submit" class="btn btn-primary">I'll make the food</button>
            </form>
          <?php else: ?>
            <button type="button" class="btn btn-secondary" disabled>
              Missing <?php echo e((int)($missingCount ?? 0)); ?>

            </button>
          <?php endif; ?>
        </div>

        <?php if(empty($ingredients) || count($ingredients) === 0): ?>
          <div class="small mt-2" style="opacity:.8;">There are no ingredients.</div>
        <?php else: ?>
          <form method="post" action="<?php echo e(route('recipes.own.missingToShopping', ['id' => (int)$recipe->id])); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="hid" value="<?php echo e((int)$hid); ?>">

            <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
              <?php $__currentLoopData = $ingredients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ing): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $has = (bool)($ing['has'] ?? false);
                  $name = (string)($ing['name'] ?? '');
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
                      <span class="badge" style="opacity:.8;">Available</span>
                    <?php else: ?>
                      <span class="badge" style="background: rgba(255,80,80,.25); border:1px solid rgba(255,80,80,.35);">Missing</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <?php if(((int)($missingCount ?? 0)) > 0): ?>
              <button type="submit" class="btn btn-primary mt-3">
                Add missing ingredients to shopping list
              </button>
            <?php else: ?>
              <button type="button" class="btn btn-secondary mt-3" disabled>
                Everything is already in stock
              </button>
            <?php endif; ?>
          </form>
        <?php endif; ?>
      </div>

      
      <div style="border:1px solid rgba(255,255,255,.12); background: rgba(255,255,255,.06); border-radius:18px; padding:16px;">
        <div style="font-weight:900; margin-bottom:10px;">Quick actions</div>

        <div style="display:grid; gap:10px;">
          <a class="btn btn-primary" href="<?php echo e(route('recipes.index', ['hid' => (int)$hid])); ?>">My recipes</a>
          <a class="btn btn-secondary" href="<?php echo e(route('recipes.own.create', ['hid' => (int)$hid])); ?>">+ New custom recipe</a>
          <a class="btn btn-secondary" href="<?php echo e(route('inventory.list', ['hid' => (int)$hid])); ?>">Inventory</a>
          <a class="btn btn-secondary" href="<?php echo e(route('dashboard')); ?>">Dashboard</a>
        </div>

        <div class="small" style="opacity:.75; margin-top:12px; line-height:1.5;">
          The cook button appears when every ingredient is available in the selected household inventory.
        </div>
      </div>
    </div>

    
    <?php if(!empty($recipe->instructions)): ?>
      <div class="mt-4" style="border:1px solid rgba(255,255,255,.12); background: rgba(0,0,0,.08); border-radius:18px; padding:16px;">
        <h3 style="margin:0 0 12px;">Preparation</h3>
        <div style="white-space: pre-line; opacity:.9; line-height:1.7;">
          <?php echo e($recipe->instructions); ?>

        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<style>
  @media (max-width: 980px){
    .card > div.mt-4 { grid-template-columns: 1fr !important; }
  }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/recipes/own_show.blade.php ENDPATH**/ ?>