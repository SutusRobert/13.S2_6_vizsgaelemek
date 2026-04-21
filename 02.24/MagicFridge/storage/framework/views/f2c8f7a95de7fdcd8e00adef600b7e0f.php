<?php $__env->startSection('title','Household - MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width: 980px; width:100%;">

    <h2>Household</h2>
    <div class="small mt-2" style="opacity:.8;">
     Household name <strong><?php echo e($household->name); ?></strong>
    </div>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <div class="mt-4">
      <h3>Invite member (from registered users)</h3>

      <form method="post" action="<?php echo e(route('households.invite')); ?>" class="mt-2">
        <?php echo csrf_field(); ?>

        <div class="form-group">
          <label>Email (exactly as it was registered)</label>
          <input type="email" name="email" required>
        </div>

        <button type="submit" class="btn btn-primary">Send invitation</button>
      </form>
    </div>

    <div class="mt-4">
      <h3>Members</h3>

      <div class="mt-3" style="display:flex; flex-direction:column; gap:12px;">
        <?php $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div style="
            display:flex;
            align-items:center;
            justify-content:flex-start;
            gap:12px;
            padding:12px 14px;
            border-radius:14px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
          ">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
              <strong><?php echo e($m->full_name); ?></strong>
              <span class="badge"><?php echo e($m->role === 'tag' ? 'member' : $m->role); ?></span>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>

    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/households/index.blade.php ENDPATH**/ ?>