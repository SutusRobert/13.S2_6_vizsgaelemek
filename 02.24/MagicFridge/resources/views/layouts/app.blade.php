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
        <span class="about-trigger">About us</span>
        <div class="about-dropdown">
          <p><strong>MagicFridge</strong> – Shared household, shared inventory, less waste.</p>
          <p>It helps you keep track of what you have at home, when something expires, and what it’s worth cooking.</p>
          <ul>
            <li>Expiration tracking and notifications</li>
            <li>Household and permissions</li>
            <li>Recipes based on your inventory</li>
            <li>Shopping list</li>
          </ul>
        </div>
      </div>

      @if(session('user_id'))
        @php
          $navHid = (int) request()->get('hid', 0);
          $recipesUrl = $navHid > 0 ? route('recipes.index', ['hid' => $navHid]) : route('recipes.index');
        @endphp

      

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn danger" type="submit">Log out</button>
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
