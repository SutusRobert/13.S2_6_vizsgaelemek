<?php $__env->startSection('title', 'Login - MagicFridge'); ?>

<?php $__env->startSection('content'); ?>
  <div class="card card-narrow">
    <h2>Login</h2>
    <p>Log in to your account to manage your recipes and household.</p>

    <?php if($errors->any()): ?>
      <div class="error mt-3"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>
    <?php if(session('status')): ?>
      <div class="success mt-3"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('login.do')); ?>">
      <?php echo csrf_field(); ?>

      <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" maxlength="40" required value="<?php echo e(old('email')); ?>">
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" maxlength="40" required>
      </div>

      <button type="submit">Login</button>

      <p class="small mt-3">No account yet? <a href="<?php echo e(route('register.form')); ?>">Register here.</a></p>
    </form>
  </div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/auth/login.blade.php ENDPATH**/ ?>