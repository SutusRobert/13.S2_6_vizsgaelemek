<!doctype html>
<html lang="hu">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'MagicFridge')</title>
  <link rel="stylesheet" href="{{ asset('assets/style.css') }}?v=4">
  @stack('head')
</head>
<body>

  <div class="navbar">
    <div class="nav-left">
      <img src="{{ asset('assets/Logo.png') }}" class="nav-logo" alt="Logo">
      <span class="nav-title"><a href="{{ route('dashboard') }}">MagicFridge</a></span>
    </div>

    <div class="nav-right">
      <div id="google_translate_element" aria-hidden="true"></div>
      <button class="translate-toggle notranslate" id="translateToggle" type="button" data-target-lang="hu">
        Translate
      </button>

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

  <script>
    window.googleTranslateElementInit = function () {
      // A Google Translate widget rejtve fut, a sajat gombunk csak ezt vezerli.
      new google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,hu',
        autoDisplay: false
      }, 'google_translate_element');

      window.MagicFridgeTranslate && window.MagicFridgeTranslate.init();
    };

    window.MagicFridgeTranslate = (() => {
      const storageKey = 'magicfridge_lang';
      const sourceLang = 'en';
      const translatedLang = 'hu';

      function setCookie(name, value) {
        // A Google Translate a googtrans cookie alapjan donti el, milyen nyelvre valtson.
        document.cookie = `${name}=${value};path=/`;
        document.cookie = `${name}=${value};path=/;domain=${location.hostname}`;
      }

      function clearTranslateCookie() {
        // Angolra visszavaltaskor torolni kell a fordito cookie-t, kulonben ujratoltes utan is forditana.
        const expired = 'Thu, 01 Jan 1970 00:00:00 GMT';
        document.cookie = `googtrans=;expires=${expired};path=/`;
        document.cookie = `googtrans=;expires=${expired};path=/;domain=${location.hostname}`;
      }

      function updateButton(lang) {
        const button = document.getElementById('translateToggle');
        if (!button) return;

        // A gomb mindig azt a nyelvet mutatja, amire a kovetkezo kattintas valtani fog.
        const isHungarian = lang === translatedLang;
        button.textContent = isHungarian ? 'English' : 'Magyar';
        button.dataset.targetLang = isHungarian ? sourceLang : translatedLang;
        button.setAttribute('aria-label', isHungarian ? 'Translate to English' : 'Translate to Hungarian');
        updateManagedLabels(lang);
      }

      function updateManagedLabels(lang) {
        // Azokat a feliratokat, amelyeket nem a Google fordit, kezzel csereljuk adat-attributumokbol.
        document.querySelectorAll('[data-label-en][data-label-hu]').forEach((el) => {
          el.textContent = lang === translatedLang ? el.dataset.labelHu : el.dataset.labelEn;
        });
      }

      function getSelect() {
        return document.querySelector('.goog-te-combo');
      }

      function chooseLanguage(lang) {
        const select = getSelect();
        if (!select) return false;

        // A rejtett Google select change esemenye inditja el tenylegesen a forditast.
        select.value = lang;
        select.dispatchEvent(new Event('change'));
        return true;
      }

      function switchLanguage(lang) {
        // A valasztott nyelvet localStorage-ben taroljuk, hogy oldalvaltas utan is megmaradjon.
        localStorage.setItem(storageKey, lang);
        updateButton(lang);

        if (lang === sourceLang) {
          // Visszavaltasnal ujratoltes kell, mert a Google widget a cookie torlese utan all vissza tisztan.
          clearTranslateCookie();
          location.reload();
          return;
        }

        setCookie('googtrans', `/${sourceLang}/${lang}`);

        if (!chooseLanguage(lang)) {
          // Ha a Google select meg nem toltott be, reload utan a cookie alapjan fogja atvenni a nyelvet.
          location.reload();
        }
      }

      function init() {
        const button = document.getElementById('translateToggle');
        if (!button) return;

        // Indulaskor a korabban mentett nyelvet vesszuk elo, alapbol angolt.
        const savedLang = localStorage.getItem(storageKey) || sourceLang;
        updateButton(savedLang);

        button.addEventListener('click', () => {
          switchLanguage(button.dataset.targetLang || translatedLang);
        });

        if (savedLang !== sourceLang) {
          // A Google widget aszinkron tolt be, ezert kis kesleltetessel probaljuk ujra alkalmazni a nyelvet.
          setTimeout(() => {
            chooseLanguage(savedLang);
            updateManagedLabels(savedLang);
          }, 500);
          setTimeout(() => updateManagedLabels(savedLang), 1200);
        }
      }

      return { init };
    })();
  </script>
  <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

  @stack('scripts')
</body>
</html>
