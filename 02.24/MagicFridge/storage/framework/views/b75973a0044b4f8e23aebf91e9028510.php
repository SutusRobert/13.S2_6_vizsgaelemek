<?php $__env->startSection('title','Inventory - MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card">

    <h2>inventory </h2>
    <p class="inv-muted mt-2">Add a new item to the household inventory.</p>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <a class="btn btn-secondary mt-3" href="<?php echo e(route('inventory.list', ['hid' => $householdId])); ?>">
      Open inventory
    </a>

    <form method="post" action="<?php echo e(route('inventory.store')); ?>" class="mt-4 inv-grid">
      <?php echo csrf_field(); ?>

      <div class="form-group">
        <label>Household</label>
        <select name="hid" class="notranslate" translate="no" required>
          <?php $__currentLoopData = $households; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option class="notranslate" translate="no" value="<?php echo e((int)$h['household_id']); ?>" <?php echo e((int)$h['household_id']===(int)$householdId ? 'selected' : ''); ?>>
              <?php echo e($h['name']); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" required value="<?php echo e(old('name')); ?>">
      </div>

      <div class="form-group">
        <label>Category (optional)</label>
        <input type="text" name="category" value="<?php echo e(old('category')); ?>">
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Location</label>
          <select name="location" class="notranslate" translate="no">
            <option class="notranslate" translate="no" value="fridge" data-label-en="Fridge" data-label-hu="Hűtő" <?php echo e(old('location')==='fridge' ? 'selected' : ''); ?>>Fridge</option>
            <option class="notranslate" translate="no" value="freezer" data-label-en="Freezer" data-label-hu="Fagyasztó" <?php echo e(old('location')==='freezer' ? 'selected' : ''); ?>>Freezer</option>
            <option class="notranslate" translate="no" value="pantry" data-label-en="Pantry" data-label-hu="Kamra" <?php echo e(old('location','pantry')==='pantry' ? 'selected' : ''); ?>>Pantry</option>
          </select>
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Quantity</label>
          <input type="number" step="0.01" name="quantity" value="<?php echo e(old('quantity', 1)); ?>">
        </div>

        <div class="form-group" style="margin-top:0;">
          <label>Unit (optional)</label>
          <input type="text" name="unit" value="<?php echo e(old('unit')); ?>">
        </div>
      </div>

      <div class="inv-filters">
        <div class="form-group" style="margin-top:0;">
          <label>Expiration date (optional)</label>
          <input type="date" name="expires_at" value="<?php echo e(old('expires_at')); ?>">
        </div>

        <div class="form-group" style="margin-top:0; grid-column: span 2;">
          <label>Note (optional)</label>
          <input type="text" name="note" value="<?php echo e(old('note')); ?>">
        </div>
      </div>

      <button type="submit">Add</button>
    </form>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/inventory/create.blade.php ENDPATH**/ ?>