<?php $__env->startSection('title', 'Bejelentkezés – MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
  <div class="card card-narrow">
    <h2>Bejelentkezés</h2>
    <p>Lépj be a fiókodba a receptek és háztartás kezeléséhez.</p>

    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('login.do')); ?>">
      <?php echo csrf_field(); ?>

      <div class="form-group">
        <label>Email cím</label>
        <input type="email" name="email" maxlength="40" required value="<?php echo e(old('email')); ?>">
      </div>

      <div class="form-group">
        <label>Jelszó</label>
        <input type="password" name="password" maxlength="40" required>
      </div>

      <button type="submit">Belépés</button>

      <p class="small mt-3">Még nincs fiókod? <a href="<?php echo e(route('register.form')); ?>">Regisztrálj itt.</a></p>
    </form>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\udvar\Documents\GitHub\13.S2_6_vizsgaelemek\02,19\MagicFridge\resources\views/auth/login.blade.php ENDPATH**/ ?>