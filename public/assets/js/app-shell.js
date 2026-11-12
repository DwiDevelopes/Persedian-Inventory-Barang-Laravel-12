/*
 * App shell: navigasi tanpa reload (PJAX ringan).
 * Hanya #page, #page-flash dan #page-stack yang diganti; sidebar, navbar, jQuery
 * dan semua library tetap hidup di memori sehingga perpindahan halaman terasa instan.
 */
(function ($, window, document) {
  'use strict';

  /* ---------- Tema terang/gelap ---------- */
  function currentTheme() { return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark'; }
  function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem('theme', t); } catch (e) {}
    $('#theme-toggle').attr('title', t === 'light' ? 'Ganti ke tema gelap' : 'Ganti ke tema terang');
    if (window.__applyChartTheme) { try { window.__applyChartTheme(); } catch (e) {} }
    $(document).trigger('themechange', [t]);
  }
  $(document).on('click.theme', '#theme-toggle', function (e) {
    e.preventDefault();
    setTheme(currentTheme() === 'light' ? 'dark' : 'light');
  });
  // sinkron antar tab
  window.addEventListener('storage', function (e) {
    if (e.key === 'theme' && (e.newValue === 'light' || e.newValue === 'dark') && e.newValue !== currentTheme()) setTheme(e.newValue);
  });
  $('#theme-toggle').attr('title', currentTheme() === 'light' ? 'Ganti ke tema gelap' : 'Ganti ke tema terang');

  var $page = $('#page');
  if (!$page.length || !window.fetch || !window.DOMParser || !history.pushState) return;

  var bar = document.getElementById('nav-progress');
  var barTimer = null, ctrl = null, prefetch = {}, hoverTimer = null;
  var loaded = {};   // src script eksternal yang sudah dimuat
  $('script[src]').each(function () { loaded[this.src] = true; });

  /* ---------- helpers ---------- */
  function normPath(p) { return p.replace(/\/+$/, '') || '/'; }

  function progress(state) {
    if (!bar) return;
    clearTimeout(barTimer);
    if (state === 'start') {
      bar.style.transition = 'none'; bar.style.width = '0'; bar.style.opacity = '1';
      void bar.offsetWidth;
      bar.style.transition = 'width 8s cubic-bezier(.1,.6,.2,1)'; bar.style.width = '85%';
    } else {
      bar.style.transition = 'width .15s ease-out'; bar.style.width = '100%';
      barTimer = setTimeout(function () { bar.style.opacity = '0'; }, 180);
    }
  }

  function eligible(a, e) {
    if (!a || !a.href) return false;
    if (e && (e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.defaultPrevented)) return false;
    if (a.target && a.target !== '_self') return false;
    if (a.hasAttribute('download') || a.hasAttribute('data-no-nav')) return false;
    if (a.origin !== location.origin) return false;
    var raw = a.getAttribute('href');
    if (!raw || raw.charAt(0) === '#' || raw.indexOf('javascript:') === 0) return false;
    if (/^\/?(logout|laporan-[a-z-]+\/print)/.test(a.pathname.replace(/^\//, ''))) return false;
    if (a.pathname === location.pathname && a.search === location.search && a.hash) return false;
    return !!$(a).closest('.sidebar-menu, .sidebar-brand, [data-nav]').length;
  }

  function hardNav(url) { window.location.href = url; }

  function request(url) {
    var hit = prefetch[url];
    if (hit && Date.now() - hit.t < 5000) { delete prefetch[url]; return hit.p; }
    if (ctrl) ctrl.abort();
    ctrl = window.AbortController ? new AbortController() : null;
    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
      signal: ctrl ? ctrl.signal : undefined
    });
  }

  /* ---------- bersih-bersih halaman lama ---------- */
  function teardown() {
    // modal terbuka / backdrop yatim
    $('.modal.show').removeClass('show').hide();
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
    // elemen yang ditempel library ke <body>
    $('body > .select2-container, body > .ui-autocomplete, body > .ui-helper-hidden-accessible, body > .tooltip').remove();
    // chart lama
    if (window.Chart && Chart.getChart) {
      $page.find('canvas').each(function () { var c = Chart.getChart(this); if (c) c.destroy(); });
    }
    // handler delegasi milik halaman (tanpa namespace) di <body> dilepas agar tidak menumpuk
    var ev = ($._data(document.body, 'events') || {});
    $.each(ev, function (type, list) {
      $.each((list || []).slice(), function (_, h) {
        if (!h.namespace) $(document.body).off(type, h.selector || null, h.handler);
      });
    });
  }

  /* ---------- eksekusi script hasil fetch ---------- */
  function runScripts(container) {
    var nodes = Array.prototype.slice.call(container.querySelectorAll('script'));
    return nodes.reduce(function (chain, old) {
      return chain.then(function () {
        return new Promise(function (resolve) {
          var el = document.createElement('script');
          for (var i = 0; i < old.attributes.length; i++) el.setAttribute(old.attributes[i].name, old.attributes[i].value);
          if (old.src) {
            if (loaded[old.src]) return resolve();
            loaded[old.src] = true;
            el.onload = el.onerror = function () { resolve(); };
            el.src = old.src;
            document.head.appendChild(el);
          } else {
            el.text = old.text;
            try { document.head.appendChild(el); } catch (err) { console.error(err); }
            document.head.removeChild(el);
            resolve();
          }
        });
      });
    }, Promise.resolve());
  }

  /* ---------- sidebar ---------- */
  function syncSidebar() {
    var path = normPath(location.pathname);
    var $menu = $('.sidebar-menu');
    $menu.find('li.active').removeClass('active');
    $menu.find('a.nav-link[href]').each(function () {
      var h = this.getAttribute('href');
      if (!h || h === '#') return;
      var p = normPath(new URL(h, location.origin).pathname);
      var on = p === '/' ? (path === '/' || path === '/dashboard') : (path === p || path.indexOf(p + '/') === 0);
      if (p === '/barang' && path.indexOf('/barang/label') === 0) on = false;
      if (!on) return;
      var $li = $(this).parent('li').addClass('active');
      var $group = $li.closest('li.dropdown');
      if ($group.length) {
        $group.addClass('active');
        $group.children('ul.dropdown-menu').slideDown(150);
      }
    });
  }

  function syncTitle() {
    var t = $page.find('.section-header h1').first().text().trim();
    $('#page-title-text').text(t);
  }

  /* ---------- ganti konten ---------- */
  function swap(doc, url, push) {
    var next = doc.getElementById('page');
    teardown();
    $page[0].innerHTML = next.innerHTML;   // native: jQuery.html() akan mengeksekusi <script> sendiri (dobel)
    var flash = document.getElementById('page-flash'), nFlash = doc.getElementById('page-flash');
    var stack = document.getElementById('page-stack'), nStack = doc.getElementById('page-stack');
    if (stack) stack.innerHTML = nStack ? nStack.innerHTML : '';
    if (flash) flash.innerHTML = nFlash ? nFlash.innerHTML : '';

    var tok = doc.querySelector('meta[name="csrf-token"]');
    if (tok) $('meta[name="csrf-token"]').attr('content', tok.content);

    document.title = doc.title || document.title;
    if (push) history.pushState({ shell: 1 }, '', url);

    // tutup sidebar mobile
    if ($('body').hasClass('sidebar-show')) $('body').removeClass('sidebar-show').addClass('sidebar-gone');

    syncSidebar();
    syncTitle();
    window.scrollTo(0, 0);

    return runScripts($page[0])
      .then(function () { return runScripts(stack); })
      .then(function () { return runScripts(flash); })
      .then(function () {
        $page.find('[data-toggle="tooltip"]').tooltip();
        $page.find('.select2').each(function () { if ($.fn.select2) $(this).select2(); });
        $(document).trigger('shell:load');
      });
  }

  function load(url, push) {
    progress('start');
    $page.addClass('is-loading');
    var p = request(url);
    Promise.resolve(p).then(function (res) {
      var finalUrl = res.url || url;
      var ct = res.headers.get('content-type') || '';
      if (!res.ok || ct.indexOf('text/html') === -1 || /\/login\/?$/.test(new URL(finalUrl).pathname)) {
        return hardNav(url);
      }
      return res.text().then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        if (!doc.getElementById('page')) return hardNav(url);
        return swap(doc, res.redirected ? finalUrl : url, push);
      });
    }).catch(function (err) {
      if (err && err.name === 'AbortError') return;
      hardNav(url);
    }).then(function () {
      $page.removeClass('is-loading');
      progress('done');
    });
  }

  /* ---------- event ---------- */
  $(document).on('click.shell', 'a', function (e) {
    if (!eligible(this, e)) return;
    var dest = this.href;
    e.preventDefault();
    if (dest === location.href) return;          // sudah di halaman ini
    load(dest, true);
  });

  // prefetch saat hover/sentuh (cache 5 detik) supaya klik terasa instan
  $(document).on('mouseover.shell touchstart.shell', 'a', function (e) {
    var a = this;
    if (!eligible(a, null) || a.href === location.href) return;
    clearTimeout(hoverTimer);
    hoverTimer = setTimeout(function () {
      var hit = prefetch[a.href];
      if (hit && Date.now() - hit.t < 5000) return;
      prefetch[a.href] = {
        t: Date.now(),
        p: fetch(a.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
      };
    }, 80);
  });

  window.addEventListener('popstate', function () {
    if (!history.state || !history.state.shell) return;
    load(location.href, false);
  });
  history.replaceState({ shell: 1 }, '', location.href);

  // keluar (konfirmasi)
  $(document).on('click.shell', '#logout-link', function (e) {
    e.preventDefault();
    var go = function () { document.getElementById('logout-form').submit(); };
    if (!window.Swal) return go();
    Swal.fire({
      title: 'Keluar dari akun?', text: 'Sesi Anda akan diakhiri.', icon: 'question',
      showCancelButton: true, confirmButtonText: 'Ya, keluar', cancelButtonText: 'Batal'
    }).then(function (r) { if (r.isConfirmed) go(); });
  });

  syncTitle();
})(jQuery, window, document);
