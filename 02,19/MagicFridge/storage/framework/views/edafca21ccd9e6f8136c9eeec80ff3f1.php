<?php $__env->startSection('title','Bevásárlólista – MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
<div class="card" style="max-width: 1100px; width:100%;">

  <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:center;">
    <div>
      <h2 style="margin-bottom:6px;">Bevásárlólista</h2>
      <div class="small">Háztartás: <strong><?php echo e($householdName); ?></strong></div>
    </div>

    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
      
      <form method="get" action="<?php echo e(route('shopping.index')); ?>" style="margin:0; display:flex; gap:10px; align-items:center;">
        <label class="small" style="opacity:.8;">Háztartás</label>
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
        <button type="button" class="btn btn-secondary" onclick="window.print()">Nyomtatás</button>

        <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;"
              onsubmit="return confirm('Biztos megveszed AZ ÖSSZES tételt? Ez fel is tölti a raktárba.');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="buy_all">
          <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
          <button type="submit" class="btn btn-secondary">Összes megvétele</button>
        </form>

        <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;"
              onsubmit="return confirm('Biztos törlöd AZ ÖSSZES tételt a bevásárlólistából?');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="clear_all">
          <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
          <button type="submit" class="btn btn-secondary">Összes törlése</button>
        </form>

        <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;"
              onsubmit="return confirm('Törlöd az összes megvett tételt?');">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="clear_bought">
          <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
          <button type="submit" class="btn btn-secondary">Megvett törlése</button>
        </form>
      </div>
    </div>
  </div>

  
  <?php if(session('success')): ?>
    <div class="success mt-3"><?php echo e(session('success')); ?></div>
  <?php endif; ?>

  <?php if($errors->any()): ?>
    <div class="error mt-3">
      <strong>Hiba:</strong>
      <ul style="margin:8px 0 0 18px;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <li><?php echo e($e); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
    </div>
  <?php endif; ?>

  
  <h3 class="mt-4">Új tétel</h3>
  <form method="post" action="<?php echo e(route('shopping.post')); ?>" class="sl-row mt-2">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Termék</label>
      <input type="text" name="name" placeholder="pl. kenyér" required>
    </div>

    <div class="form-group" style="flex: 0 0 140px;">
      <label>Mennyiség</label>
      <input type="number" step="0.01" name="quantity" value="1">
    </div>

    <div class="form-group" style="flex: 0 0 160px;">
      <label>Egység</label>
      <input type="text" name="unit" placeholder="db / kg / l">
    </div>

    <div class="form-group" style="flex: 0 0 170px;">
      <label>Hely (raktár)</label>
      <select name="location">
        <option value="fridge">Hűtő</option>
        <option value="freezer">Fagyasztó</option>
        <option value="pantry" selected>Kamra</option>
      </select>
    </div>

    <div class="form-group" style="flex: 1 1 260px;">
      <label>Megjegyzés</label>
      <input type="text" name="note" placeholder="pl. teljes kiőrlésű">
    </div>

    <div style="flex:0 0 auto;">
      <button type="submit">Hozzáadás</button>
    </div>
  </form>

  <h3 class="mt-4">Lista</h3>

  <div class="mt-3" style="display:flex; flex-direction:column; gap:10px;">
    <?php if(empty($items)): ?>
      <div class="small" style="opacity:.8;">Nincs tétel.</div>
    <?php endif; ?>

    <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
        $bought = ((int)($it->is_bought ?? 0) === 1);

        $loc = (string)($it->location ?? 'pantry');
        $locLabel = $loc === 'fridge' ? 'Hűtő' : ($loc === 'freezer' ? 'Fagyasztó' : 'Kamra');

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
              <?php echo e($bought ? 'Vissza' : 'Megvett'); ?>

            </button>
          </form>

          <div>
            <div class="sl-name <?php echo e($bought ? 'sl-done' : ''); ?>">
              <?php echo e($it->name); ?>

              <span class="small" style="opacity:.75;">
                — <?php echo e($qty); ?> <?php echo e($unit); ?>

              </span>
              <span class="small" style="opacity:.75;"> • <?php echo e($locLabel); ?></span>
            </div>

            <?php if($note !== ''): ?>
              <div class="sl-meta"><?php echo e($note); ?></div>
            <?php endif; ?>

            <?php if($bought && $boughtAt !== ''): ?>
              <div class="sl-meta">Megvéve: <?php echo e($boughtAt); ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="sl-actions">
          
          <form method="post" action="<?php echo e(route('shopping.post')); ?>" style="margin:0;" onsubmit="return confirm('Biztos törlöd?');">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="hid" value="<?php echo e((int)$householdId); ?>">
            <input type="hidden" name="id" value="<?php echo e((int)$it->id); ?>">
            <button type="submit" class="btn btn-secondary btn-mini">Törlés</button>
          </form>
        </div>
      </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  </div>

  <div class="small mt-4" style="opacity:.75;">
    Tipp: ha “Megvett”-re nyomsz, a tétel automatikusan felkerül a raktárba a kiválasztott helyre.
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\udvar\Documents\GitHub\13.S2_6_vizsgaelemek\02,19\MagicFridge\resources\views/shopping/index.blade.php ENDPATH**/ ?>