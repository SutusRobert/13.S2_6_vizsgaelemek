<?php $__env->startSection('title','Shopping List - MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="card" style="max-width: 1100px; width:100%;">

  <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
    <div>
      <h2 style="margin-bottom:6px;">Shopping list</h2>
      <div class="small">Household: <strong><?php echo e($householdName); ?></strong></div>
    </div>

    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
      
      <form method="get" action="<?php echo e(route('shopping.index')); ?>" style="margin:0; display:flex; gap:10px; align-items:center;">
        <label class="small" style="opacity:.8;">Household</label>
        <select name="hid" onchange="this.form.submit()">
          <?php $__currentLoopData = $households; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $hidOpt = (int)$hh->household_id; ?>
            <option value="<?php echo e($hidOpt); ?>" <?php echo e($hidOpt === (int)$householdId ? 'selected' : ''); ?>>
              <?php echo e($hh->name); ?>

            </option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </form>

      
      <div class="sl-printbar">
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>

        <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;"
              onsubmit="return confirm('Are you sure you want to buy ALL items? This also adds them to inventory.');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
          <button type="submit" name="action" value="buy_all" class="btn btn-secondary">Buy all</button>
        </form>

        <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;"
              onsubmit="return confirm('Are you sure you want to delete ALL items from the shopping list?');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
          <button type="submit" name="action" value="clear_all" class="btn btn-secondary">Delete all</button>
        </form>

        <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;"
              onsubmit="return confirm('Do you want to delete all purchased items?');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
          <button type="submit" name="action" value="clear_bought" class="btn btn-secondary">Delete purchased items</button>
        </form>
      </div>
    </div>
  </div>

  
  <?php if(session('success')): ?>
    <div class="success mt-3"><?php echo e(session('success')); ?></div>
  <?php endif; ?>

  <?php if($errors->any()): ?>
    <div class="error mt-3">
      <strong>Error:</strong>
      <ul style="margin:8px 0 0 18px;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li><?php echo e($e); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>

  
  <h3 class="mt-4">New item</h3>
  <form method="post" action="<?php echo e(route('shopping.post')); ?>" class="sl-row mt-2">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Item</label>
      <input type="text" name="name" placeholder="for example : Bread " required>
    </div>

    <div class="form-group" style="flex: 0 0 140px;">
      <label>Quantity</label>
      <input type="number" step="0.01" name="quantity" value="1">
    </div>

    <div class="form-group" style="flex: 0 0 160px;">
      <label>Unit</label>
      <input type="text" name="unit" placeholder="pcs / kg / l">
    </div>

    <div class="form-group" style="flex: 0 0 170px;">
      <label>Location (inventory)</label>
      <select name="location">
        <option value="auto" selected>Auto</option>
        <option value="fridge">Fridge</option>
        <option value="freezer">Freezer</option>
        <option value="pantry">Pantry</option>
      </select>
    </div>

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Note</label>
      <input type="text" name="note" placeholder="e.g. whole grain">
    </div>

    <div style="flex:0 0 auto;">
      <button type="submit">Add</button>
    </div>
  </form>

  <h3 class="mt-4">list</h3>

  <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
    <?php if(empty($items)): ?>
      <div class="small" style="opacity:.8;">No Item</div>
    <?php endif; ?>

    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
        $bought = ((int)($it->is_bought ?? 0) === 1);

        $loc = (string)($it->location ?? 'pantry');
        $locLabel = $loc === 'fridge' ? 'Fridge' : ($loc === 'freezer' ? 'Freezer' : 'Pantry');

        $qty = (string)($it->quantity ?? '1');
        $unit = (string)($it->unit ?? '');
        $note = (string)($it->note ?? '');
        $boughtAt = (string)($it->bought_at ?? '');
      ?>

      <div class="sl-item">
        <div class="sl-left">
          
          <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
            <input type="hidden" name="id" value="<?php echo e((int)$it->id); ?>">
            <input type="hidden" name="to" value="<?php echo e($bought ? 0 : 1); ?>">
            <button type="submit" class="btn btn-secondary btn-mini">
              <?php echo e($bought ? 'Back' : 'Bought'); ?>

            </button>
          </form>

          <div>
            <div class="sl-name <?php echo e($bought ? 'sl-done' : ''); ?>">
              <?php echo e($it->name); ?>

              <span class="small" style="opacity:.75;">
                - <?php echo e($qty); ?> <?php echo e($unit); ?>

              </span>
              <span class="small" style="opacity:.75;"> - <?php echo e($locLabel); ?></span>
            </div>

            <?php if($note !== ''): ?>
              <div class="sl-meta"><?php echo e($note); ?></div>
            <?php endif; ?>

            <?php if($bought && $boughtAt !== ''): ?>
              <div class="sl-meta">Bought: <?php echo e($boughtAt); ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="sl-actions">
          
          <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this item?');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
            <input type="hidden" name="id" value="<?php echo e((int)$it->id); ?>">
            <button type="submit" class="btn btn-secondary btn-mini">Delete</button>
          </form>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  <div class="small mt-4" style="opacity:.75;">
Tip: if you click "Bought", the item will automatically be added to the inventory in the selected location.
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/shopping/index.blade.php ENDPATH**/ ?>