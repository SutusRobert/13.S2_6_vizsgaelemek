<?php $__env->startSection('title','Készlet – MagicFridge'); ?>

<?php
  $today = new DateTime('today');
  $soon = (clone $today)->modify('+3 days');
?>

<?php $__env->startSection('content'); ?>
<div class="main-wrapper">
  <div class="card" style="max-width:1200px; width:100%;">

    <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap;">
      <div>
        <h2 style="margin-bottom:6px;">Készlet</h2>
        <div class="small">Háztartás: <strong><?php echo e($householdName); ?></strong></div>
      </div>
      <a class="btn btn-secondary" href="<?php echo e(route('inventory.create', ['hid' => $householdId])); ?>">+ Új termék</a>
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
    <label>Keresés</label>
    <input type="text" name="q" value="<?php echo e($q); ?>">
  </div>

  <div class="form-group" style="margin-top:0;">
    <label>Hely</label>
    <select name="loc">
      <option value="">Minden</option>
      <option value="fridge"  <?php echo e($loc==='fridge'?'selected':''); ?>>Hűtő</option>
      <option value="freezer" <?php echo e($loc==='freezer'?'selected':''); ?>>Fagyasztó</option>
      <option value="pantry"  <?php echo e($loc==='pantry'?'selected':''); ?>>Kamra</option>
    </select>
  </div>

  <div style="display:flex; gap:10px; align-items:end;">
    <button type="submit">Szűrés</button>
    <a class="btn btn-secondary" href="<?php echo e(route('inventory.list', ['hid'=>$householdId])); ?>">Reset</a>
  </div>
</form>


<div style="min-width:260px;">
  <label class="small" style="opacity:.85;">Háztartás</label>

  <form method="get" action="<?php echo e(route('inventory.list')); ?>">
    
    <input type="hidden" name="q" value="<?php echo e((string)($q ?? '')); ?>">
    <input type="hidden" name="loc" value="<?php echo e((string)($loc ?? '')); ?>">

    <select name="hid" onchange="this.form.submit()">
      <?php $__currentLoopData = ($households ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hh): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $hhId = (int)($hh['household_id'] ?? $hh->household_id ?? 0);
          $hhName = (string)($hh['name'] ?? $hh->name ?? '');
        ?>
        <option value="<?php echo e($hhId); ?>" <?php echo e($hhId === (int)($householdId ?? $householdId ?? 0) ? 'selected' : ''); ?>>
          <?php echo e($hhName); ?>

        </option>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
  </form>
</div>


    <div class="mt-4">
      <?php if(empty($items)): ?>
        <p class="inv-muted">Még nincs termék a raktárban.</p>
      <?php else: ?>
        <table class="inv-table">
          <thead>
            <tr>
              <th>Termék</th>
              <th>Hely</th>
              <th>Mennyiség</th>
              <th>Lejárat</th>
              <th style="text-align:right;">Művelet</th>
            </tr>
          </thead>
          <tbody>
          <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
              $badgeClass = 'badge-ok'; $badgeText = 'OK';
              if (!empty($it->expires_at)) {
                $d = new DateTime($it->expires_at);
                if ($d < $today) { $badgeClass='badge-danger'; $badgeText='Lejárt'; }
                elseif ($d <= $soon) { $badgeClass='badge-warn'; $badgeText='Hamarosan'; }
              }
              $locText = $it->location==='fridge' ? 'Hűtő' : ($it->location==='freezer' ? 'Fagyasztó' : 'Kamra');
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

                    <select name="location">
                      <option value="fridge"  <?php echo e($it->location==='fridge'?'selected':''); ?>>Hűtő</option>
                      <option value="freezer" <?php echo e($it->location==='freezer'?'selected':''); ?>>Fagyasztó</option>
                      <option value="pantry"  <?php echo e($it->location==='pantry'?'selected':''); ?>>Kamra</option>
                    </select>

                    <input type="number" step="0.01" name="quantity" value="<?php echo e($it->quantity); ?>" style="max-width:110px;">
                    <input type="date" name="expires_at" value="<?php echo e($it->expires_at); ?>" style="max-width:150px;">

                    <button type="submit" class="btn-mini">Mentés</button>
                  </form>
                  
                  <form method="post" action="<?php echo e(route('inventory.list.post')); ?>" style="display:inline; margin:0;" onsubmit="return confirm('Biztos törlöd?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo e((int)$it->id); ?>">
                    <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
                    <?php if($q !== ''): ?> <input type="hidden" name="q" value="<?php echo e($q); ?>"> <?php endif; ?>
                    <?php if($loc !== ''): ?> <input type="hidden" name="loc" value="<?php echo e($loc); ?>"> <?php endif; ?>
                    <button type="submit" class="btn btn-secondary btn-mini">Törlés</button>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\project\MagicFridge\resources\views/inventory/list.blade.php ENDPATH**/ ?>