

<?php $__env->startSection('title', 'Regisztráció – MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
  <div class="card card-narrow">
    <h2>Regisztráció</h2>

    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('register.do')); ?>">
      <?php echo csrf_field(); ?>

      <div class="form-group">
        <label>Teljes név</label>
        <input type="text" name="full_name" maxlength="40" required value="<?php echo e(old('full_name')); ?>">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" maxlength="40" required value="<?php echo e(old('email')); ?>">
      </div>

      <div class="form-group">
        <label>Jelszó</label>
        <input type="password" name="password" maxlength="40" required>
      </div>

      <div class="form-group">
        <label>Jelszó újra</label>
        <input type="password" name="password_confirmation" maxlength="40" required>
      </div>

      <button type="submit">Regisztráció</button>

      <p class="small mt-3">Van már fiókod? <a href="<?php echo e(route('login.form')); ?>">Lépj be itt.</a></p>
    </form>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\SutusRobert\projects\magicfridge\resources\views/auth/register.blade.php ENDPATH**/ ?>