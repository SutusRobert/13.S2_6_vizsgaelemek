<?php $__env->startSection('title', 'Dashboard – MagicFridge'); ?>

<?php $__env->startPush('head'); ?>
<style>
  /* A buborékok csak háttérelemek, ezért nem kattinthatók. */
  .bubbles{
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
  }
  .navbar, .dash-row { position: relative; z-index: 2; }
  .dash-side .side-stack > .note:nth-of-type(2){ display: none; }

  .dash-row{
    max-width: 1750px;
    margin: 0 auto;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 28px;
    padding: 18px 28px 40px;
    box-sizing: border-box;
  }

  .dash-left, .dash-side{
    width: 420px;
    flex: 0 0 420px;
    min-width: 0;
  }

  .dash-mid{
    flex: 1 1 auto;
    min-width: 560px;
    max-width: 980px;
  }

  .main-wrapper{ margin: 0; width: 100%; }

  @media (max-width: 1280px){
    .dash-row{
      flex-wrap: wrap;
      justify-content: center;
    }

    .dash-left,
    .dash-side{
      width: min(420px, 100%);
      flex: 1 1 360px;
    }

    .dash-mid{
      order: -1;
      min-width: 0;
      flex: 1 1 100%;
      max-width: 980px;
    }
  }

  @media (max-width: 760px){
    .dash-row{
      display: block;
      padding: 14px 12px 28px;
    }

    .dash-left,
    .dash-mid,
    .dash-side{
      width: 100%;
      max-width: 100%;
      min-width: 0;
      margin-bottom: 14px;
    }

    .fridge-card,
    .side-card{
      width: 100%;
    }
  }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="dash-row">

  <!-- Bal oldali hűtődoboz -->
  <div class="dash-left">
    <div class="fridge-card">
      <div class="fridge-hero">
        <img src="<?php echo e(asset('assets/logo.png')); ?>" alt="Fridge" class="fridge-img">
      </div>
      <p></p>
      <div class="fridge-body">
        <span class="pill">🧊 Inventory</span>
        <h3 style="margin-top:10px;">Inventory & Expiration Dates</h3>
        <p class="small mt-2">
         Track your products, quantities, and expiration dates all in one place.
        </p>
      </div>
    </div>
  </div>

  <!-- Középső dashboard kártya -->
  <div class="dash-mid">
    <div class="main-wrapper">
      <div class="card">

        <h1>Hello, <?php echo e($firstName); ?>! 👋</h1>
        <p class="mt-2">Choose a module</p>

        <div class="menu-grid mt-4">

          <a href="<?php echo e(route('recipes.index')); ?>" class="menu-tile">
            <div class="menu-icon">🍳</div>
            <div class="menu-title">Recipes</div>
            <div class="menu-desc">See what you can make with your current stock.</div>
            <div class="menu-go">Open →</div>
          </a>

         <a href="<?php echo e(route('messages.index')); ?>" class="menu-tile">
            <div class="menu-icon">🔔</div>
            <div class="menu-title">Messages</div>
            <div class="menu-desc">Expiration dates, warnings, notifications.</div>
            <div class="menu-go">Open →</div>
          </a>

          <a href="<?php echo e(route('households.index')); ?>" class="menu-tile">
            <div class="menu-icon">🧺</div>
            <div class="menu-title">Household</div>
            <div class="menu-desc">Member management, roles, access.</div>
            <div class="menu-go">Open →</div>
          </a>

          <a href="<?php echo e(route('inventory.create')); ?>" class="menu-tile">
            <div class="menu-icon">🧊</div>
            <div class="menu-title">Inventory</div>
            <div class="menu-desc">Stock, quantity, expiration dates..</div>
            <div class="menu-go">Open →</div>
          </a>

          <a href="<?php echo e(route('shopping.index')); ?>" class="menu-tile menu-tile--wide">
            <div class="menu-icon">🛒</div>
            <div class="menu-title">Shopping list</div>
            <div class="menu-desc">The household’s shared list. After checking items off, they can be added to the inventory.</div>
            <div class="menu-go">Open →</div>
          </a>

        </div>

      </div>
    </div>
  </div>

  <!-- Jobb oldali tippek doboz -->
  <div class="dash-side">
    <div class="card side-card">
      <div class="side-stack">

        <div class="note">
          <div style="font-weight:900; margin-bottom:8px;">✨ Daily tip</div>
          <div id="dashTip">Write the date on containers: 10 seconds now, days less waste later.</div>
        </div>

        <div class="note">
          <div style="font-weight:900; margin-bottom:10px;">⚡ Quick actions</div>
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <a class="btn btn-mini" href="#">🔔 Messages (<?php echo e($unreadCount > 0 ? ($unreadCount.' new') : 'No new messages'); ?>)</a>
            <a class="btn btn-mini" href="#">🧊 inventory</a>
            <a class="btn btn-mini" href="#">🛒 Shopping list</a>
            <a class="btn btn-mini" href="#">🍳 Recipes</a>
          </div>
        </div>

        <div class="note">
          <div style="font-weight:900; margin-bottom:8px;">🎯 Mini challenge</div>
          <div id="dashMission">Add one item to the shopping list that you always forget.</div>
        </div>

      </div>
    </div>
  </div>

</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
/* Buborék háttér mozgatása: véletlen kezdés + enyhe parallax egérmozgásra. */
(() => {
  const bubbles = document.getElementById('bubbles');
  if (!bubbles) return;

  // Minden buborék kap saját sebességet/mélységet, ettől nem egyszerre mozognak.
  const items = Array.from(bubbles.querySelectorAll('span')).map((el, i) => {
    const dur = parseFloat(getComputedStyle(el).animationDuration) || 20;
    el.style.animationDelay = (Math.random() * dur * -1).toFixed(2) + 's';
    const speed = 0.6 + (i % 7) * 0.15;
    const depth = 8 + (i % 6) * 6;
    return { el, speed, depth };
  });

  let mx = 0, my = 0, tx = 0, ty = 0;
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  window.addEventListener('mousemove', (e) => {
    // Az egér pozícióját -1..1 tartományra normalizáljuk a képernyő közepéhez képest.
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    mx = clamp((e.clientX - cx) / cx, -1, 1);
    my = clamp((e.clientY - cy) / cy, -1, 1);
  }, { passive: true });

  function tick() {
    // A tx/ty lassan közelíti a célértéket, ezért a parallax nem rángat, hanem csúszik.
    tx += (mx - tx) * 0.06;
    ty += (my - ty) * 0.06;

    const sy = window.scrollY || 0;
    for (const it of items) {
      // Mélységtől függően minden buborék kicsit más mértékben mozdul el.
      const px = tx * it.depth * it.speed;
      const py = ty * it.depth * it.speed + (sy * 0.02 * it.speed);
      it.el.style.transform = `translate3d(${px.toFixed(2)}px, ${py.toFixed(2)}px, 0)`;
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\sutus\OneDrive\Dokumentumok\GitHub\13.S2_6_vizsgaelemek\02.24\MagicFridge\resources\views/inventory/dashboard.blade.php ENDPATH**/ ?>