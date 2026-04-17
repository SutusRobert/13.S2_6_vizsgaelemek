<?php $__env->startSection('title','Háztartás – MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <h2>Háztartás</h2>
    <div class="small mt-2" style="opacity:.8;">
      Háztartás neve: <strong><?php echo e($household->name); ?></strong>
    </div>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <div class="mt-4">
      <h3>Tag meghívása (regisztrált felhasználók közül)</h3>

      <form method="post" action="<?php echo e(route('households.invite')); ?>" class="mt-2">
        <?php echo csrf_field(); ?>

        <div class="form-group">
          <label>Email (pontosan úgy, ahogy regisztrálva van)</label>
          <input type="email" name="email" required>
        </div>

        <button type="submit" class="btn btn-primary">Meghívás küldése</button>
      </form>
    </div>

    <div class="mt-4">
      <h3>Tagok</h3>

      <div class="mt-3" style="display:flex; flex-direction:column; gap:12px;">
        <?php $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $isOwner = (int)$household->owner_id === (int)session('user_id');
            $canPromote = $isOwner && ((string)$m->role !== 'admin'); // a jelenlegi logikád ezt engedi
          ?>

          <div style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
            padding:12px 14px;
            border-radius:14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
          ">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
              <strong><?php echo e($m->full_name); ?></strong>
              <span class="badge"><?php echo e($m->role); ?></span>
            </div>

            <div>
              <?php if($canPromote): ?>
                <form method="post" action="<?php echo e(route('households.toggleRole')); ?>" style="margin:0;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="hm_id" value="<?php echo e($m->hm_id); ?>">
                  <button class="btn btn-secondary" type="submit">Rang hozzáadása</button>
                </form>
              <?php else: ?>
                
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\udvar\Documents\GitHub\13.S2_6_vizsgaelemek\02,19\MagicFridge\resources\views/households/index.blade.php ENDPATH**/ ?>