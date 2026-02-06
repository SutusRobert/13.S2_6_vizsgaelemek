<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'MagicFridge')</title>
  <link rel="stylesheet" href="{{ asset('assets/style.css') }}?v=1">
  @stack('head')
</head>
<body>

  {{-- Buborék háttér --}}
  <div class="bubbles" aria-hidden="true">
    @for($i=0; $i<20; $i++)
      <span></span>
    @endfor
  </div>

  <div class="navbar">
    <div class="nav-left">
      <img src="{{ asset('assets/Logo.png') }}" class="nav-logo" alt="Logo">
      <span class="nav-title"><a href="{{ route('dashboard') }}">MagicFridge</a></span>
        


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

      @if(session('user_id'))
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn danger" type="submit">Kijelentkezés</button>
        </form>
      @endif
    </div>
  </div>

  <div class="main-wrapper">
    @yield('content')
  </div>

  @stack('scripts')
</body>
</html>
