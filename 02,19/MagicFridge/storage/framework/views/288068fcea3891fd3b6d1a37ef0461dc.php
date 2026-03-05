<?php $__env->startSection('title','Raktár – MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card">

    <h2>Raktár</h2>
    <p class="inv-muted mt-2">Adj hozzá új terméket a háztartás készletéhez.</p>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <a class="btn btn-secondary mt-3" href="<?php echo e(route('inventory.list', ['hid' => $householdId])); ?>">
      Készlet megnyitása
    </a>

    <form method="post" action="<?php echo e(route('inventory.store')); ?>" class="mt-4 inv-grid">
      <?php echo csrf_field(); ?>

      <div class="form-group">
        <label>Háztartás</label>
        <select name="hid" required>
          <?php $__currentLoopData = $households; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e((int)$h['household_id']); ?>" <?php echo e((int)$h['household_id']===(int)$householdId ? 'selected' : ''); ?>>
              <?php echo e($h['name']); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <div class="form-group">
        <label>Név</label>
        <input type="text" name="name" required value="<?php echo e(old('name')); ?>">
      </div>

      <div class="form-group">
        <label>Kategória (opcionális)</label>
        <input type="text" name="category" value="<?php echo e(old('category')); ?>">
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Hely</label>
          <select name="location">
            <option value="fridge" <?php echo e(old('location')==='fridge' ? 'selected' : ''); ?>>Hűtő</option>
            <option value="freezer" <?php echo e(old('location')==='freezer' ? 'selected' : ''); ?>>Fagyasztó</option>
            <option value="pantry" <?php echo e(old('location','pantry')==='pantry' ? 'selected' : ''); ?>>Kamra</option>
          </select>
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Mennyiség</label>
          <input type="number" step="0.01" name="quantity" value="<?php echo e(old('quantity', 1)); ?>">
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Egység (opcionális)</label>
          <input type="text" name="unit" value="<?php echo e(old('unit')); ?>">
        </div>
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Lejárat (opcionális)</label>
          <input type="date" name="expires_at" value="<?php echo e(old('expires_at')); ?>">
        </div>

        <div class="form-group" style="margin-top:0; grid-column: span 2;">
          <label>Megjegyzés (opcionális)</label>
          <input type="text" name="note" value="<?php echo e(old('note')); ?>">
        </div>
      </div>

      <button type="submit">Hozzáadás</button>
    </form>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\udvar\Documents\GitHub\13.S2_6_vizsgaelemek\02,19\MagicFridge\resources\views/inventory/create.blade.php ENDPATH**/ ?>