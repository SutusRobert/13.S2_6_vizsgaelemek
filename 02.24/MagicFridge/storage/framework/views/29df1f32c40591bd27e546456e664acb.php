<?php $__env->startSection('title', 'Messages - MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
  <div class="card">
    <h2>Messages</h2>

    <?php if(session('success')): ?>
      <div class="success mt-3"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <?php if(empty($messages)): ?>
      <p class="mt-3">You have no messages.</p>
    <?php else: ?>
      <div class="mt-3">
        <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $isRead = (int)($m->is_read ?? 0) === 1;
            $title = (string)($m->title ?? 'Message');
            $body  = (string)($m->body ?? '');
            $link  = (string)($m->link_url ?? '');

            $isInvite = str_starts_with($link, 'invite:');
            $isInventory = str_starts_with($link, 'inventory:');
            $hid = $isInventory ? (int)substr($link, strlen('inventory:')) : 0;
          ?>

          <div class="card mt-3" style="padding:16px;">
            <div style="display:flex; justify-content:space-between; gap:10px; align-items:center;">
              <div>
                <strong><?php echo e($title); ?></strong>
                <?php if(!$isRead): ?>
                  <span class="badge" style="margin-left:8px;">New</span>
                <?php endif; ?>
              </div>
              <div style="opacity:.7; font-size:12px;">
                <?php echo e($m->created_at ?? ''); ?>

              </div>
            </div>

            <?php if($body !== ''): ?>
              <p class="mt-2"><?php echo e($body); ?></p>
            <?php endif; ?>

            <?php if($isInventory && $hid > 0): ?>
              <div class="mt-2">
                <a class="btn" href="<?php echo e(route('inventory.list', ['hid' => $hid])); ?>">Open inventory</a>
              </div>
            <?php endif; ?>

            <div class="mt-3" style="display:flex; gap:10px; flex-wrap:wrap;">
              
              <?php if($isInvite): ?>
                <form method="post" action="<?php echo e(route('messages.invite.respond')); ?>">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo e((int)$m->id); ?>">
                  <input type="hidden" name="action" value="accept">
                  <button class="btn" type="submit">Accept</button>
                </form>

                <form method="post" action="<?php echo e(route('messages.invite.respond')); ?>">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo e((int)$m->id); ?>">
                  <input type="hidden" name="action" value="decline">
                  <button class="btn danger" type="submit">Decline</button>
                </form>
              <?php endif; ?>

                <form method="post" action="<?php echo e(route('messages.delete')); ?>" style="margin:0; display:inline-block;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo e((int)$m->id); ?>">
                  <button type="submit" class="btn btn-secondary">Disappear</button>
                </form>

              
              <?php if(!$isRead): ?>
                <form method="post" action="<?php echo e(route('messages.read')); ?>">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="id" value="<?php echo e((int)$m->id); ?>">
                  
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    <?php endif; ?>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/messages/index.blade.php ENDPATH**/ ?>