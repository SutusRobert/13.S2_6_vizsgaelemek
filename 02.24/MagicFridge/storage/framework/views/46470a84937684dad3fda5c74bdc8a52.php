<?php $__env->startSection('title','Inventory - MagicFridge'); ?>

<?php
  // A lejárati badge-ekhez egyszer számoljuk ki a mai napot és a "hamarosan" határt.
  $today = new DateTime('today');
  $soon = (clone $today)->modify('+3 days');
?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width:1200px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
      <div>
        <h2 style="margin-bottom:6px;">Inventory</h2>
        <div class="small">Household: <strong><?php echo e($householdName); ?></strong></div>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <a class="btn btn-secondary" href="<?php echo e(route('inventory.create', ['hid' => $householdId])); ?>">+ New item</a>

        <form method="post" action="<?php echo e(route('inventory.list.post')); ?>" style="margin:0;"
              onsubmit="return confirm('Are you sure you want to delete ALL inventory items for this household?');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="delete_all">
          <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
          <button type="submit" class="btn btn-secondary">Delete all</button>
        </form>
      </div>
    </div>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <form method="get" action="<?php echo e(route('inventory.list')); ?>" class="mt-4 inv-filters">
  <input type="hidden" name="hid" value="<?php echo e($householdId); ?>">

  <div class="form-group" style="margin-top:0;">
    <label>Search</label>
    <input type="text" name="q" value="<?php echo e($q); ?>">
  </div>

  <div class="form-group" style="margin-top:0;">
    <label>Location</label>
    <select name="loc" class="notranslate" translate="no">
      <option class="notranslate" translate="no" value="" data-label-en="All of them" data-label-hu="Mindegyik">All of them</option>
      <option class="notranslate" translate="no" value="fridge" data-label-en="Fridge" data-label-hu="Hűtő" <?php echo e($loc==='fridge'?'selected':''); ?>>Fridge</option>
      <option class="notranslate" translate="no" value="freezer" data-label-en="Freezer" data-label-hu="Fagyasztó" <?php echo e($loc==='freezer'?'selected':''); ?>>Freezer</option>
      <option class="notranslate" translate="no" value="pantry" data-label-en="Pantry" data-label-hu="Kamra" <?php echo e($loc==='pantry'?'selected':''); ?>>Pantry</option>
    </select>
  </div>

  <div style="display:flex; gap:10px; align-items:end;">
    <button type="submit">Filter</button>
    <a class="btn btn-secondary" href="<?php echo e(route('inventory.list', ['hid'=>$householdId])); ?>">Reset</a>
  </div>
</form>


<div style="min-width:260px;">
  <label class="small" style="opacity:.85;">Household</label>

  <form method="get" action="<?php echo e(route('inventory.list')); ?>">
    
    <input type="hidden" name="q" value="<?php echo e((string)($q ?? '')); ?>">
    <input type="hidden" name="loc" value="<?php echo e((string)($loc ?? '')); ?>">

    <select name="hid" class="notranslate" translate="no" onchange="this.form.submit()">
      <?php $__currentLoopData = ($households ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $hhId = (int)($hh['household_id'] ?? $hh->household_id ?? 0);
          $hhName = (string)($hh['name'] ?? $hh->name ?? '');
        ?>
        <option class="notranslate" translate="no" value="<?php echo e($hhId); ?>" <?php echo e($hhId === (int)($householdId ?? $householdId ?? 0) ? 'selected' : ''); ?>>
          <?php echo e($hhName); ?>

        </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </form>
</div>


    <div class="mt-4">
      <?php if(empty($items)): ?>
        <p class="inv-muted">There are no items in the warehouse yet.</p>
      <?php else: ?>
        <table class="inv-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Location</th>
              <th>Quantity</th>
              <th>Expiration date</th>
              <th style="text-align:right;">Action</th>
            </tr>
          </thead>
          <tbody>
          <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              // Badge algoritmus: lejárt, 3 napon belül lejár, vagy rendben van.
              $badgeClass = 'badge-ok'; $badgeText = 'OK';
              if (!empty($it->expires_at)) {
                $d = new DateTime($it->expires_at);
                if ($d < $today) { $badgeClass='badge-danger'; $badgeText='Expired'; }
                elseif ($d <= $soon) { $badgeClass='badge-warn'; $badgeText='Soon'; }
              }
              $locText = $it->location==='fridge' ? 'Fridge' : ($it->location==='freezer' ? 'Freezer' : 'Pantry');
            ?>

            <tr>
              <td><strong><?php echo e($it->name); ?></strong></td>
              <td><?php echo e($locText); ?></td>
              <td><?php echo e($it->quantity); ?> <?php echo e($it->unit); ?></td>
              <td>
                <?php if(!empty($it->expires_at)): ?>
                  <span class="badge <?php echo e($badgeClass); ?>"><?php echo e($badgeText); ?></span>
                  <span class="inv-muted"> <?php echo e($it->expires_at); ?></span>
                <?php else: ?>
                  <span class="inv-muted">—</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;">
                <div class="inv-actions">

                  <form method="post" action="/inventory/list" style="display:inline-flex; gap:8px; align-items:center; margin:0;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo e((int)$it->id); ?>">
                    <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
                    <?php if($q !== ''): ?> <input type="hidden" name="q" value="<?php echo e($q); ?>"> <?php endif; ?>
                    <?php if($loc !== ''): ?> <input type="hidden" name="loc" value="<?php echo e($loc); ?>"> <?php endif; ?>

                    <select name="location" class="notranslate" translate="no">
                      <option class="notranslate" translate="no" value="fridge" data-label-en="Fridge" data-label-hu="Hűtő" <?php echo e($it->location==='fridge'?'selected':''); ?>>Fridge</option>
                      <option class="notranslate" translate="no" value="freezer" data-label-en="Freezer" data-label-hu="Fagyasztó" <?php echo e($it->location==='freezer'?'selected':''); ?>>Freezer</option>
                      <option class="notranslate" translate="no" value="pantry" data-label-en="Pantry" data-label-hu="Kamra" <?php echo e($it->location==='pantry'?'selected':''); ?>>Pantry</option>
                    </select>

                    <input type="number" step="0.01" name="quantity" value="<?php echo e($it->quantity); ?>" style="max-width:110px;">
                    <input type="date" name="expires_at" value="<?php echo e($it->expires_at); ?>" style="max-width:150px;">

                    <button type="submit" class="btn-mini">Save</button>
                  </form>
                  
                  <form method="post" action="<?php echo e(route('inventory.list.post')); ?>" style="display:inline; margin:0;" onsubmit="return confirm('Are you sure you want to delete it?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo e((int)$it->id); ?>">
                    <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
                    <?php if($q !== ''): ?> <input type="hidden" name="q" value="<?php echo e($q); ?>"> <?php endif; ?>
                    <?php if($loc !== ''): ?> <input type="hidden" name="loc" value="<?php echo e($loc); ?>"> <?php endif; ?>
                    <button type="submit" class="btn btn-secondary btn-mini">Delete</button>
                  </form>
                  
                </div>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/inventory/list.blade.php ENDPATH**/ ?>