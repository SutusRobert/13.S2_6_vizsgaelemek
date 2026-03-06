<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $__env->yieldContent('title', 'MagicFridge'); ?></title>
  <link rel="stylesheet" href="<?php echo e(asset('assets/style.css')); ?>?v=1">
  <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body>

  
  <div class="bubbles" aria-hidden="true">
    <?php for($i=0; $i<20; $i++): ?>
      <span></span>
    <?php endfor; ?>
  </div>

  <div class="navbar">
    <div class="nav-left">
      <img src="<?php echo e(asset('assets/Logo.png')); ?>" class="nav-logo" alt="Logo">
      <span class="nav-title"><a href="<?php echo e(route('dashboard')); ?>">MagicFridge</a></span>
        <a href="<?php echo e(route('inventory.create')); ?>">Raktár</a>
        <a href="<?php echo e(route('inventory.list')); ?>">Készlet</a>


    </div>

    <div class="nav-right">
      <div class="about-nav">
        <span class="about-trigger">Rólunk</span>
        <div class="about-dropdown">
          <p><strong>MagicFridge</strong> – közös háztartás, közös készlet, kevesebb pazarlás.</p>
          <p>Segít nyomon követni, mi van otthon, mikor jár le valami, és mit érdemes főzni.</p>
          <ul>
            <li>Lejáratfigyelés és értesítések</li>
            <li>Háztartás és jogosultságok</li>
            <li>Receptek a készlet alapján</li>
            <li>Bevásárlólista</li>
          </ul>
        </div>
      </div>

      <?php if(session('user_id')): ?>
        <form method="POST" action="<?php echo e(route('logout')); ?>">
          <?php echo csrf_field(); ?>
          <button class="btn danger" type="submit">Kijelentkezés</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="main-wrapper">
    <?php echo $__env->yieldContent('content'); ?>
  </div>

  <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\Users\SutusRobert\projects\magicfridge\resources\views/layouts/app.blade.php ENDPATH**/ ?>