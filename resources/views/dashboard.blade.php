@extends('layouts.app')

@php
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $num = fn ($n) => number_format((int) $n, 0, ',', '.');

    $kpis = [
        ['label' => 'Jenis Barang',      'value' => $num($kpi['barang']),        'sub' => 'item terdaftar',        'icon' => 'fa-cubes'],
        ['label' => 'Total Stok',        'value' => $num($kpi['total_stok']),    'sub' => 'unit di gudang',        'icon' => 'fa-warehouse'],
        ['label' => 'Nilai Persediaan',  'value' => $rp($kpi['nilai']),          'sub' => 'stok × harga beli',     'icon' => 'fa-coins', 'small' => true],
        ['label' => 'Stok Menipis',      'value' => $num($kpi['menipis']),       'sub' => 'di bawah minimum',      'icon' => 'fa-exclamation-triangle'],
        ['label' => 'Stok Habis',        'value' => $num($kpi['habis']),         'sub' => 'perlu restock',         'icon' => 'fa-box-open'],
        ['label' => 'Masuk Bulan Ini',   'value' => $num($kpi['masuk_bulan']),   'sub' => 'unit',                  'icon' => 'fa-arrow-down'],
        ['label' => 'Keluar Bulan Ini',  'value' => $num($kpi['keluar_bulan']),  'sub' => 'unit',                  'icon' => 'fa-arrow-up'],
        ['label' => 'Pengguna',          'value' => $num($user),                 'sub' => 'akun',                  'icon' => 'fa-users'],
    ];
@endphp

@section('content')
<div class="section-header">
  <img class="page-logo" src="{{ asset('assets/img/stocku-logo.png') }}" alt="">
  <h1>Dashboard</h1>
  <span class="ml-auto dash-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
</div>

<div class="card kpi-strip kpi-wide">
  @foreach ($kpis as $k)
    <div class="kpi">
      <div class="kpi-label"><span>{{ $k['label'] }}</span><i class="fas {{ $k['icon'] }}"></i></div>
      <div class="kpi-value" @if(!empty($k['small'])) style="font-size:1.35rem" @endif>{{ $k['value'] }}</div>
      <div class="kpi-sub">{{ $k['sub'] }}</div>
    </div>
  @endforeach
</div>

<div class="row dash-grid">
  <div class="col-xl-8 col-12">
    <div class="card">
      <div class="card-header split">
        <h4>Barang Masuk &amp; Keluar per Bulan</h4>
        <div class="legend">
          <span><i style="background:var(--series-1)"></i>Masuk</span>
          <span><i style="background:var(--series-2)"></i>Keluar</span>
        </div>
      </div>
      <div class="card-body">
        @if (count($charts['bulanan']['labels']))
          <div class="chart-box md"><canvas id="chartBulanan" aria-label="Grafik barang masuk dan keluar per bulan" role="img"></canvas></div>
        @else
          <div class="empty-state"><i class="far fa-chart-bar"></i>Belum ada transaksi untuk ditampilkan.</div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-xl-4 col-12">
    <div class="card">
      <div class="card-header split"><h4>Status Stok</h4></div>
      <div class="card-body">
        @if (array_sum($charts['status']['data']) > 0)
          <div class="chart-box md"><canvas id="chartStatus" aria-label="Komposisi status stok barang" role="img"></canvas></div>
        @else
          <div class="empty-state"><i class="far fa-chart-bar"></i>Belum ada barang.</div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row dash-grid">
  <div class="col-12">
    <div class="card">
      <div class="card-header split">
        <h4>Tren Harian (30 Hari Terakhir)</h4>
        <div class="legend">
          <span><i style="background:var(--series-1)"></i>Masuk</span>
          <span><i style="background:var(--series-2)"></i>Keluar</span>
        </div>
      </div>
      <div class="card-body"><div class="chart-box sm"><canvas id="chartHarian" aria-label="Tren barang masuk dan keluar harian" role="img"></canvas></div></div>
    </div>
  </div>
</div>

<div class="row dash-grid">
  <div class="col-xl-6 col-12">
    <div class="card">
      <div class="card-header split"><h4>Stok per Jenis Barang</h4></div>
      <div class="card-body">
        @if (count($charts['jenis']['labels']))
          <div class="chart-box md"><canvas id="chartJenis" aria-label="Jumlah stok per jenis barang" role="img"></canvas></div>
        @else
          <div class="empty-state"><i class="far fa-chart-bar"></i>Belum ada data.</div>
        @endif
      </div>
    </div>
  </div>
  <div class="col-xl-6 col-12">
    <div class="card">
      <div class="card-header split"><h4>Stok vs Batas Minimum <small class="text-muted ml-2">8 barang paling rawan</small></h4></div>
      <div class="card-body">
        @if (count($charts['stokMin']['labels']))
          <div class="chart-box md"><canvas id="chartStokMin" aria-label="Perbandingan stok dan stok minimum" role="img"></canvas></div>
        @else
          <div class="empty-state"><i class="far fa-chart-bar"></i>Belum ada barang dengan stok minimum.</div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row dash-grid">
  <div class="col-xl-6 col-12">
    <div class="card">
      <div class="card-header split"><h4>Top 5 Barang Keluar <small class="text-muted ml-2">30 hari</small></h4></div>
      <div class="card-body">
        @if (count($charts['top']['keluar']['labels']))
          <div class="chart-box sm"><canvas id="chartTopKeluar" aria-label="Lima barang paling banyak keluar" role="img"></canvas></div>
        @else
          <div class="empty-state"><i class="far fa-chart-bar"></i>Belum ada barang keluar.</div>
        @endif
      </div>
    </div>
  </div>
  <div class="col-xl-6 col-12">
    <div class="card">
      <div class="card-header split"><h4>Top 5 Barang Masuk <small class="text-muted ml-2">30 hari</small></h4></div>
      <div class="card-body">
        @if (count($charts['top']['masuk']['labels']))
          <div class="chart-box sm"><canvas id="chartTopMasuk" aria-label="Lima barang paling banyak masuk" role="img"></canvas></div>
        @else
          <div class="empty-state"><i class="far fa-chart-bar"></i>Belum ada barang masuk.</div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row dash-grid">
  <div class="col-xl-6 col-12">
    <div class="card">
      <div class="card-header split">
        <h4>Stok Mencapai Batas Minimum</h4>
        @if ($barangMinimum->count())
          <span class="badge badge-warning">{{ $barangMinimum->count() }} item</span>
        @endif
      </div>
      <div class="card-body card-flush">
        @if ($barangMinimum->count())
          <div class="table-scroll">
            <table class="table stock-table">
              <thead>
                <tr><th>Kode</th><th>Nama Barang</th><th class="qty">Stok</th><th class="qty">Min.</th></tr>
              </thead>
              <tbody>
                @foreach ($barangMinimum as $item)
                  <tr>
                    <td class="code">{{ $item->kode_barang }}</td>
                    <td>{{ $item->nama_barang }}</td>
                    <td class="qty"><span class="badge {{ (int) $item->stok <= 0 ? 'badge-danger' : 'badge-warning' }}">{{ (int) $item->stok }}</span></td>
                    <td class="qty">{{ (int) $item->stok_minimum }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="empty-state"><i class="far fa-check-circle"></i>Semua stok di atas batas minimum.</div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-xl-6 col-12">
    <div class="card">
      <div class="card-header split"><h4>Transaksi Terbaru</h4></div>
      <div class="card-body card-flush">
        @if ($terbaru->count())
          <div class="table-scroll">
            <table class="table stock-table">
              <thead><tr><th>Tanggal</th><th>Kode</th><th>Barang</th><th class="qty">Jumlah</th></tr></thead>
              <tbody>
                @foreach ($terbaru as $t)
                  <tr>
                    <td>{{ \Illuminate\Support\Str::of($t['tanggal'])->substr(0, 10) }}</td>
                    <td class="code">{{ $t['kode'] }}</td>
                    <td>{{ $t['barang'] }}</td>
                    <td class="qty {{ $t['jenis'] === 'masuk' ? 'trx-in' : 'trx-out' }}">{{ $t['jenis'] === 'masuk' ? '+' : '−' }}{{ (int) $t['jumlah'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="empty-state"><i class="far fa-clock"></i>Belum ada transaksi.</div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('assets/js/stocku-charts.js') }}?v=1"></script>
<script>
(function () {
  if (!window.Chart || !window.StockuCharts) return;
  var D = @json($charts);
  var $ = function (id) { return document.getElementById(id); };
  var intTicks = { precision: 0 };

  // 1. Masuk & keluar per bulan
  StockuCharts.make($('chartBulanan'), {
    type: 'bar',
    data: { labels: D.bulanan.labels, datasets: [
      { label: 'Masuk',  data: D.bulanan.masuk,  cssColor: '--series-1', borderRadius: 3, maxBarThickness: 22 },
      { label: 'Keluar', data: D.bulanan.keluar, cssColor: '--series-2', borderRadius: 3, maxBarThickness: 22 }
    ]},
    options: {
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { display: false } },
      scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: intTicks, border: { display: false } } }
    }
  });

  // 2. Status stok (doughnut) - warna semantik
  StockuCharts.make($('chartStatus'), {
    type: 'doughnut',
    data: { labels: D.status.labels, datasets: [{ data: D.status.data, semantic: ['#3fb950', '#e0a13a', '#f0615a'], borderWidth: 2 }] },
    options: { cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } } }
  });

  // 3. Tren harian
  StockuCharts.make($('chartHarian'), {
    type: 'line',
    data: { labels: D.harian.labels, datasets: [
      { label: 'Masuk',  data: D.harian.masuk,  cssColor: '--series-1', fill: true, tension: .3, pointRadius: 2, borderWidth: 2 },
      { label: 'Keluar', data: D.harian.keluar, cssColor: '--series-2', fill: true, tension: .3, pointRadius: 2, borderWidth: 2 }
    ]},
    options: {
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { display: false } },
      scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }, y: { beginAtZero: true, ticks: intTicks, border: { display: false } } }
    }
  });

  // 4. Stok per jenis
  StockuCharts.make($('chartJenis'), {
    type: 'bar',
    data: { labels: D.jenis.labels, datasets: [{ label: 'Total stok', data: D.jenis.stok, palette: true, borderRadius: 3, maxBarThickness: 34 }] },
    options: {
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: { x: { beginAtZero: true, ticks: intTicks, border: { display: false } }, y: { grid: { display: false } } }
    }
  });

  // 5. Stok vs minimum
  StockuCharts.make($('chartStokMin'), {
    type: 'bar',
    data: { labels: D.stokMin.labels, datasets: [
      { label: 'Stok',    data: D.stokMin.stok,    cssColor: '--series-1', borderRadius: 3, maxBarThickness: 16 },
      { label: 'Minimum', data: D.stokMin.minimum, cssColor: '--series-2', borderRadius: 3, maxBarThickness: 16 }
    ]},
    options: {
      indexAxis: 'y',
      interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
      scales: { x: { beginAtZero: true, ticks: intTicks, border: { display: false } }, y: { grid: { display: false } } }
    }
  });

  // 6 & 7. Top 5 keluar / masuk
  function top(id, data, css) {
    StockuCharts.make($(id), {
      type: 'bar',
      data: { labels: data.labels, datasets: [{ label: 'Jumlah', data: data.data, cssColor: css, borderRadius: 3, maxBarThickness: 26 }] },
      options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: intTicks, border: { display: false } }, y: { grid: { display: false } } }
      }
    });
  }
  top('chartTopKeluar', D.top.keluar, '--series-2');
  top('chartTopMasuk',  D.top.masuk,  '--series-1');
})();
</script>
@endpush
