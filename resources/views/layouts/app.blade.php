<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="color-scheme" content="dark light">
  <!-- Terapkan tema tersimpan sebelum render supaya tidak berkedip -->
  <script>
    (function () {
      var t = null;
      try { t = localStorage.getItem('theme'); } catch (e) {}
      document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
    })();
  </script>
  <title>Stocku</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/img/stocku-logo.png') }}">

  <!-- Base -->
  <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">

  <!-- Libraries -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">

  <!-- Dark theme (harus paling akhir) -->
  <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v=9">

  <!-- jQuery dimuat satu kali; script halaman memakainya saat parsing -->
  <script src="{{ asset('assets/modules/jquery.min.js') }}"></script>
</head>

<body>
  <div id="nav-progress" aria-hidden="true"></div>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <nav class="navbar navbar-expand-lg main-navbar">
        <ul class="navbar-nav mr-auto">
          <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg" aria-label="Buka/tutup menu"><i class="fas fa-bars"></i></a></li>
          <li class="nav-page-title d-none d-sm-block"><span id="page-title-text"></span></li>
        </ul>
        <ul class="navbar-nav navbar-right">
          <li>
            <a href="#" id="theme-toggle" class="nav-link nav-link-lg theme-toggle" role="button" aria-label="Ganti tema terang/gelap" title="Ganti tema">
              <i class="fas fa-sun ti-sun"></i><i class="fas fa-moon ti-moon"></i>
            </a>
          </li>
          <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
              <img alt="" src="{{ asset('assets/img/avatar/avatar-1.png') }}" class="rounded-circle mr-1">
              <div class="d-none d-lg-inline-block">
                <span class="user-name">{{ auth()->user()->name }}</span>
                <small class="user-role">{{ auth()->user()->role->role ?? '' }}</small>
              </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
              <a href="/ubah-password" class="dropdown-item has-icon" data-nav>
                <i class="fas fa-lock"></i> Ubah Password
              </a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item has-icon" href="{{ route('logout') }}" id="logout-link">
                <i class="fas fa-sign-out-alt"></i> {{ __('Keluar') }}
              </a>
              <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
          </li>
        </ul>
      </nav>

      <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
          <div class="sidebar-brand">
            <a href="/" data-nav><img class="brand-logo" src="{{ asset('assets/img/stocku-logo.png') }}" alt=""><span>Stocku</span></a>
          </div>
          @include('layouts.partials.sidebar')
        </aside>
      </div>

      <!-- Konten (area yang diganti saat navigasi tanpa reload) -->
      <div class="main-content" id="page">
        <section class="section">
          @yield('content')
        </section>
        <div id="page-modals">@yield('modals')</div>
      </div>

      <footer class="main-footer">
        <div class="footer-left">&copy; {{ date('Y') }} Stocku</div>
      </footer>
    </div>
  </div>

  <!-- General JS -->
  <script src="{{ asset('assets/modules/popper.js') }}"></script>
  <script src="{{ asset('assets/modules/tooltip.js') }}"></script>
  <script src="{{ asset('assets/modules/bootstrap/js/bootstrap.min.js') }}"></script>
  <script src="{{ asset('assets/modules/nicescroll/jquery.nicescroll.min.js') }}"></script>
  <script src="{{ asset('assets/modules/moment.min.js') }}"></script>
  <script src="{{ asset('assets/js/stisla.js') }}"></script>

  <!-- Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js" integrity="sha256-lSjKY0/srUM9BE3dPm+c4fBo1dky2v27Gdjm2uoZaL0=" crossorigin="anonymous"></script>

  <script src="{{ asset('assets/js/scripts.js') }}"></script>
  <script src="{{ asset('assets/js/custom.js') }}"></script>

  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

  <div id="page-flash">@include('sweetalert::alert')</div>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
  <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>

  <!-- Scanner barcode (dipakai di beberapa halaman; modal berada di luar area yang diganti saat navigasi) -->
  <div class="modal fade" id="scanner-modal" tabindex="-1" role="dialog" aria-labelledby="scanner-title">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="scanner-title"><i class="fas fa-barcode mr-1"></i> Scan Barcode</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <select id="scanner-camera" class="form-control mb-2 d-none" aria-label="Pilih kamera"></select>
          <div id="scanner-reader" class="scanner-reader"></div>
          <div id="scanner-error" class="alert alert-warning mt-2 d-none" role="alert"></div>
          <div class="text-muted small mt-2">Arahkan kamera ke barcode. Tidak ada kamera? Ketik kode barang di bawah.</div>
          <div class="input-group mt-2">
            <input type="text" id="scanner-manual" class="form-control" placeholder="Ketik kode barang, mis. BRG-00001" autocomplete="off">
            <div class="input-group-append"><button type="button" class="btn btn-primary" id="scanner-manual-ok">OK</button></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="{{ asset('assets/js/stocku-scan.js') }}?v=1"></script>

  <!-- Script khusus halaman (ikut diganti saat navigasi) -->
  <div id="page-stack">@stack('scripts')</div>

  <!-- Navigasi tanpa reload -->
  <script src="{{ asset('assets/js/app-shell.js') }}?v=4"></script>
</body>
</html>
