@extends('layouts.app')

@section('content')
<div class="section-header">
    <h1>Kartu Stok</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="form-row">
            <div class="form-group col-lg-5">
                <label>Barang</label>
                <div class="input-group scan-input-group">
                    <select id="ks_barang" class="form-control" style="width:calc(100% - 46px)">
                        <option value="">Pilih Barang</option>
                        @foreach ($barangs as $b)
                            <option value="{{ $b->kode_barang }}">{{ $b->kode_barang }} — {{ $b->nama_barang }}</option>
                        @endforeach
                    </select>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-primary" id="ks_scan" title="Scan barcode"><i class="fas fa-barcode"></i></button>
                    </div>
                </div>
            </div>
            <div class="form-group col-6 col-lg-2">
                <label>Dari</label>
                <input type="date" id="ks_dari" class="form-control">
            </div>
            <div class="form-group col-6 col-lg-2">
                <label>Sampai</label>
                <input type="date" id="ks_sampai" class="form-control">
            </div>
            <div class="form-group col-lg-3 d-flex align-items-end">
                <button type="button" class="btn btn-primary btn-block" id="ks_tampil"><i class="fas fa-search"></i> Tampilkan</button>
            </div>
        </div>
    </div>
</div>

<div id="ks_hasil" class="d-none">
    <div class="card kpi-strip kpi-wide">
        <div class="kpi"><div class="kpi-label"><span>Saldo Awal</span></div><div class="kpi-value" id="ks_awal">0</div></div>
        <div class="kpi"><div class="kpi-label"><span>Total Masuk</span></div><div class="kpi-value trx-in" id="ks_masuk">0</div></div>
        <div class="kpi"><div class="kpi-label"><span>Total Keluar</span></div><div class="kpi-value trx-out" id="ks_keluar">0</div></div>
        <div class="kpi"><div class="kpi-label"><span>Saldo Akhir</span></div><div class="kpi-value" id="ks_akhir">0</div><div class="kpi-sub" id="ks_satuan"></div></div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header split"><h4 id="ks_judul">Pergerakan Saldo</h4></div>
                <div class="card-body"><div class="chart-box sm"><canvas id="chartKartu" role="img" aria-label="Grafik saldo stok"></canvas></div></div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table_id" class="display">
                            <thead><tr><th>Tanggal</th><th>Kode Transaksi</th><th>Jenis</th><th>Masuk</th><th>Keluar</th><th>Saldo</th><th>Keterangan</th></tr></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card" id="ks_kosong"><div class="card-body"><div class="empty-state"><i class="fas fa-stream"></i>Pilih atau scan barang untuk melihat mutasinya.</div></div></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('assets/js/stocku-charts.js') }}?v=1"></script>
<script>
(function () {
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const table = $('#table_id').DataTable({ paging: true, order: [] });
    const JENIS = { masuk: ['Masuk', 'trx-in'], keluar: ['Keluar', 'trx-out'], penyesuaian: ['Penyesuaian', 'trx-adj'] };
    let chart = null;
    $('#ks_barang').select2({ width: '100%' });

    function tampil() {
        const kode = $('#ks_barang').val();
        if (!kode) return;
        $.getJSON('/kartu-stok/get-data', { kode: kode, dari: $('#ks_dari').val(), sampai: $('#ks_sampai').val() }).done(function (r) {
            $('#ks_kosong').addClass('d-none'); $('#ks_hasil').removeClass('d-none');
            const akhir = r.rows.length ? r.rows[r.rows.length - 1].saldo : r.saldo_awal;
            $('#ks_awal').text(r.saldo_awal); $('#ks_masuk').text(r.total_masuk); $('#ks_keluar').text(r.total_keluar);
            $('#ks_akhir').text(akhir); $('#ks_satuan').text(r.barang.satuan);
            $('#ks_judul').text('Pergerakan Saldo — ' + r.barang.nama_barang);

            table.clear();
            r.rows.forEach(function (x) {
                const j = JENIS[x.jenis] || [x.jenis, ''];
                table.row.add([esc(x.tanggal), esc(x.kode), `<span class="${j[1]}">${j[0]}</span>`,
                    x.masuk ? `<b class="trx-in">+${x.masuk}</b>` : '', x.keluar ? `<b class="trx-out">−${x.keluar}</b>` : '',
                    `<b>${x.saldo}</b>`, esc(x.ket || '-')]);
            });
            table.draw(false);

            const labels = ['Awal'].concat(r.rows.map((x) => x.tanggal.substr(5) + ' ' + (JENIS[x.jenis] || [x.jenis])[0]));
            const saldo = [r.saldo_awal].concat(r.rows.map((x) => x.saldo));
            const min = r.barang.stok_minimum;
            if (chart) { chart.destroy(); chart = null; }
            chart = StockuCharts.make(document.getElementById('chartKartu'), {
                type: 'line',
                data: { labels: labels, datasets: [
                    { label: 'Saldo', data: saldo, cssColor: '--series-1', fill: true, stepped: true, pointRadius: 3, borderWidth: 2 },
                    { label: 'Stok minimum', data: saldo.map(() => min), cssColor: '--series-2', borderDash: [6, 4], pointRadius: 0, borderWidth: 1.5 }
                ]},
                options: {
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }, y: { beginAtZero: true, ticks: { precision: 0 }, border: { display: false } } }
                }
            });
        });
    }
    $('#ks_tampil').on('click', tampil);
    $('#ks_barang').on('change', tampil);
    $('#ks_scan').on('click', function () {
        StockuScan.open(function (kode) {
            if ($('#ks_barang option[value="' + kode.replace(/"/g, '') + '"]').length) { $('#ks_barang').val(kode).trigger('change'); }
            else { Swal.fire({ icon: 'warning', title: 'Kode tidak terdaftar', text: kode }); }
        });
    });

    // buka langsung dari halaman scan (?kode=...)
    const q = new URLSearchParams(location.search).get('kode');
    if (q && $('#ks_barang option[value="' + q.replace(/"/g, '') + '"]').length) { $('#ks_barang').val(q).trigger('change'); }
})();
</script>
@endpush
