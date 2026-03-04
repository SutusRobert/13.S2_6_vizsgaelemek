@extends('layouts.app')

@section('title', 'Dashboard – MagicFridge')

@push('head')
<style>
  /* Bubik tényleg háttér */
  .bubbles{
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
  }
  .navbar, .dash-row { position: relative; z-index: 2; }

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
</style>
@endpush

@section('content')
<div class="dash-row">

  <!-- BAL: HŰTŐ BOX -->
  <div class="dash-left">
    <div class="fridge-card">
      <div class="fridge-hero">
        <img src="{{ asset('assets/logo.png') }}" alt="Hűtő" class="fridge-img">
      </div>
      <p></p>
      <div class="fridge-body">
        <span class="pill">🧊 Inventory</span>
        <h3 style="margin-top:10px;">Inventory & Expiration Dates</h3>
        <p class="small mt-2">
         Track your products, quantities, and expiration dates all in one place.
        </p>

        <div class="mt-3" style="display:flex; gap:10px; flex-wrap:wrap;">
          <a class="btn" href="#">Open warehouse</a>
      
        </div>
      </div>
    </div>
  </div>

  <!-- KÖZÉP: DASHBOARD CARD -->
  <div class="dash-mid">
    <div class="main-wrapper">
      <div class="card">

        <h1>Hello, {{ $firstName }}! 👋</h1>
        <p class="mt-2">Choose a module</p>

        <div class="menu-grid mt-4">

          <a href="{{ route('recipes.index') }}" class="menu-tile">
            <div class="menu-icon">🍳</div>
            <div class="menu-title">Recipes</div>
            <div class="menu-desc">See what you can make with your current stock.</div>
            <div class="menu-go">Open →</div>
          </a>

         <a href="{{ route('messages.index') }}" class="menu-tile">
            <div class="menu-icon">🔔</div>
            <div class="menu-title">Messages</div>
            <div class="menu-desc">Expiration dates, warnings, notifications.</div>
            <div class="menu-go">Open →</div>
          </a>

          <a href="{{ route('households.index') }}" class="menu-tile">
            <div class="menu-icon">🧺</div>
            <div class="menu-title">Household</div>
            <div class="menu-desc">Member management, roles, access.</div>
            <div class="menu-go">Open →</div>
          </a>

          <a href="{{ route('inventory.create') }}" class="menu-tile">
            <div class="menu-icon">🧊</div>
            <div class="menu-title">Inventory</div>
            <div class="menu-desc">Stock, quantity, expiration dates..</div>
            <div class="menu-go">Open →</div>
          </a>

          <a href="{{ route('shopping.index') }}" class="menu-tile menu-tile--wide">
            <div class="menu-icon">🛒</div>
            <div class="menu-title">Shopping list</div>
            <div class="menu-desc">The household’s shared list. After checking items off, they can be added to the inventory.</div>
            <div class="menu-go">Open →</div>
          </a>

        </div>

        <div class="dash-notify mt-4" aria-live="polite">
          <div class="dn-head">
            <div class="dn-left">
              <span class="dn-ico">🔔</span>
              <span class="dn-title">Recent notifications</span>
            </div>
            <div class="dn-badge {{ $unreadCount > 0 ? 'is-on' : '' }}">
              {{ $unreadCount > 0 ? ($unreadCount . ' új') : 'Nincs új' }}
            </div>
          </div>

          @if($unreadCount > 0)
            <div class="dn-list">
              @foreach($unreadPreview as $m)
                <div class="dn-item">
                  <div class="dn-item-title">{{ $m->title ?? 'Értesítés' }}</div>
                  <div class="dn-item-desc">
                    {{ \Illuminate\Support\Str::limit(strip_tags($m->body ?? ''), 110, '…') }}
                  </div>
                  <div class="dn-item-meta">{{ $m->created_at ?? '' }}</div>
                </div>
              @endforeach
            </div>
            <div class="dn-foot">
              <span class="dn-hint">If you handle it under Messages (mark as read / accept / decline), it will automatically disappear from here.</span>
            </div>
          @else
            <div class="dn-empty">Everything’s fine — there are no new messages.</div>
          @endif
        </div>

      </div>
    </div>
  </div>

  <!-- JOBB: TIPPEK BOX -->
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
            <a class="btn btn-mini" href="#">🔔 Messages ({{ $unreadCount > 0 ? ($unreadCount.' új') : 'No new messages' }})</a>
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
@endsection

@push('scripts')
<script>
/* Bubik random indulás + parallax (a régi kód) */
(() => {
  const bubbles = document.getElementById('bubbles');
  if (!bubbles) return;

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
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    mx = clamp((e.clientX - cx) / cx, -1, 1);
    my = clamp((e.clientY - cy) / cy, -1, 1);
  }, { passive: true });

  function tick() {
    tx += (mx - tx) * 0.06;
    ty += (my - ty) * 0.06;

    const sy = window.scrollY || 0;
    for (const it of items) {
      const px = tx * it.depth * it.speed;
      const py = ty * it.depth * it.speed + (sy * 0.02 * it.speed);
      it.el.style.transform = `translate3d(${px.toFixed(2)}px, ${py.toFixed(2)}px, 0)`;
    }
    requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
})();
</script>
@endpush
