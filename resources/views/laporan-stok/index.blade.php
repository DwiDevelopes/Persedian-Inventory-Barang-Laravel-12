@extends('layouts.app')

@section('content')

<div class="section-header">
    <h1>Laporan Stok</h1>
    <div class="ml-auto">
        <a href="javascript:void(0)" class="btn btn-danger" id="print-stok"><i class="fa fa-sharp fa-light fa-print"></i> Print PDF</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <label for="opsi-laporan-stok">Filter Stok Berdasarkan :</label>
                    <select class="form-control" name="opsi-laporan-stok" id="opsi-laporan-stok">
                        <option value="semua" selected>Semua</option>
                        <option value="aman">Stok Aman</option>
                        <option value="minimum">Batas Minimum (menipis)</option>
                        <option value="stok-habis">Stok Habis</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card kpi-strip kpi-wide">
            <div class="kpi"><div class="kpi-label"><span>Jumlah Barang</span></div><div class="kpi-value" id="s_barang">0</div></div>
            <div class="kpi"><div class="kpi-label"><span>Total Stok</span></div><div class="kpi-value" id="s_stok">0</div></div>
            <div class="kpi"><div class="kpi-label"><span>Nilai Persediaan</span></div><div class="kpi-value" id="s_nilai" style="font-size:1.35rem">Rp 0</div></div>
        </div>

        <div class="row">
            <div class="col-xl-6 col-12">
                <div class="card">
                    <div class="card-header split"><h4>Nilai Persediaan per Jenis</h4></div>
                    <div class="card-body"><div class="chart-box sm"><canvas id="chartNilaiJenis" role="img" aria-label="Nilai persediaan per jenis barang"></canvas></div></div>
                </div>
            </div>
            <div class="col-xl-6 col-12">
                <div class="card">
                    <div class="card-header split"><h4>10 Barang Bernilai Persediaan Tertinggi</h4></div>
                    <div class="card-body"><div class="chart-box sm"><canvas id="chartNilaiTop" role="img" aria-label="Sepuluh barang dengan nilai persediaan tertinggi"></canvas></div></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="table_id" class="display">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th>Jenis</th>
                                <th>Lokasi</th>
                                <th>Stok</th>
                                <th>Min.</th>
                                <th>Harga Beli</th>
                                <th>Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-laporan-stok">
                        </tbody>
                    </table>
                </div>
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
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const rp = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
    const STATUS = { aman: 'Aman', menipis: 'Menipis', habis: 'Habis' };
    const table = $('#table_id').DataTable({ paging: true });
    let cJenis = null, cTop = null;

    function charts(items) {
        const per = {};
        items.forEach(function (b) { per[b.jenis] = (per[b.jenis] || 0) + b.nilai; });
        const jl = Object.keys(per), jv = jl.map((k) => per[k]);
        const top = items.slice().sort((a, b) => b.nilai - a.nilai).slice(0, 10);
        const tick = { callback: (v) => Number(v).toLocaleString('id-ID') };
        const tip = { callbacks: { label: (c) => ' ' + rp(c.raw) } };

        if (cJenis) cJenis.destroy(); if (cTop) cTop.destroy();
        cJenis = StockuCharts.make(document.getElementById('chartNilaiJenis'), {
            type: 'bar',
            data: { labels: jl, datasets: [{ label: 'Nilai', data: jv, palette: true, borderRadius: 3, maxBarThickness: 40 }] },
            options: { plugins: { legend: { display: false }, tooltip: tip }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: tick, border: { display: false } } } }
        });
        cTop = StockuCharts.make(document.getElementById('chartNilaiTop'), {
            type: 'bar',
            data: { labels: top.map((b) => b.nama_barang), datasets: [{ label: 'Nilai', data: top.map((b) => b.nilai), cssColor: '--series-1', borderRadius: 3, maxBarThickness: 18 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: tip }, scales: { x: { beginAtZero: true, ticks: tick, border: { display: false } }, y: { grid: { display: false } } } }
        });
    }

    function loadData(opsi) {
        $.getJSON('/laporan-stok/get-data', { opsi: opsi }, function (items) {
            table.clear();
            items.forEach(function (b, i) {
                table.row.add([
                    i + 1, `<span class="kode-badge">${esc(b.kode_barang)}</span>`, esc(b.nama_barang), esc(b.jenis), esc(b.lokasi || '-'),
                    b.stok + ' ' + esc(b.satuan), b.stok_minimum, rp(b.harga_beli), rp(b.nilai),
                    `<span class="badge badge-status-${b.status}">${STATUS[b.status]}</span>`
                ]);
            });
            table.draw();
            $('#s_barang').text(items.length);
            $('#s_stok').text(items.reduce((a, b) => a + b.stok, 0).toLocaleString('id-ID'));
            $('#s_nilai').text(rp(items.reduce((a, b) => a + b.nilai, 0)));
            charts(items);
        });
    }

    loadData('semua');
    $('#opsi-laporan-stok').on('change', function () { loadData($(this).val()); });
    $('#print-stok').on('click', function () { window.location.href = '/laporan-stok/print-stok?opsi=' + $('#opsi-laporan-stok').val(); });
})();
</script>
@endpush
