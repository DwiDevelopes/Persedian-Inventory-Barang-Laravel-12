@extends('layouts.app')

@section('content')
<div class="section-header">
    <h1>Penyesuaian Stok (Stok Opname)</h1>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h4>Catat Hasil Hitung Fisik</h4></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" class="form-control" id="adj_tanggal">
                    <div class="alert alert-danger mt-2 d-none" id="alert-tanggal"></div>
                </div>

                <div class="form-group">
                    <label>Scan Barcode / Kode Barang</label>
                    <div class="input-group scan-input-group">
                        <input type="text" class="form-control" id="adj_scan" placeholder="Scan atau ketik kode, Enter" autocomplete="off">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-primary" id="adj_camera" title="Scan kamera"><i class="fas fa-camera"></i></button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Barang</label>
                    <select id="adj_barang" class="form-control" style="width:100%">
                        <option value="">Pilih Barang</option>
                        @foreach ($barangs as $b)
                            <option value="{{ $b->kode_barang }}">{{ $b->kode_barang }} — {{ $b->nama_barang }}</option>
                        @endforeach
                    </select>
                    <div class="alert alert-danger mt-2 d-none" id="alert-kode_barang"></div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label>Stok Sistem</label>
                        <input type="text" class="form-control" id="adj_sistem" disabled>
                    </div>
                    <div class="form-group col-6">
                        <label>Stok Fisik</label>
                        <input type="number" class="form-control" id="adj_fisik" min="0" step="1">
                    </div>
                </div>
                <div class="alert alert-danger d-none" id="alert-stok_fisik"></div>
                <div class="mb-3">Selisih: <b id="adj_selisih">-</b></div>

                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" class="form-control" id="adj_ket" maxlength="255" placeholder="mis. rusak, hilang, salah input">
                </div>

                <button type="button" class="btn btn-primary btn-block" id="adj_simpan"><i class="fas fa-check"></i> Simpan Penyesuaian</button>
                <small class="text-muted d-block mt-2">Stok barang akan diubah menjadi nilai stok fisik dan tercatat di Kartu Stok.</small>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h4>Riwayat Penyesuaian</h4></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="table_id" class="display">
                        <thead>
                            <tr>
                                <th>Tanggal</th><th>Kode</th><th>Barang</th><th>Sistem</th><th>Fisik</th><th>Selisih</th><th>Keterangan</th><th>Oleh</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const table = $('#table_id').DataTable({ paging: true, order: [] });
    let sistem = null;

    function hariIni() { const d = new Date(), p = (n) => String(n).padStart(2, '0'); return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()); }
    $('#adj_tanggal').val(hariIni());
    $('#adj_barang').select2({ width: '100%' });

    function loadTable() {
        $.getJSON('/penyesuaian-stok/get-data', function (res) {
            table.clear();
            res.data.forEach(function (r) {
                const cls = r.selisih > 0 ? 'trx-in' : (r.selisih < 0 ? 'trx-out' : '');
                table.row.add([
                    esc(String(r.tanggal).substr(0, 10)), esc(r.kode_transaksi),
                    `<span class="kode-badge">${esc(r.kode_barang)}</span> ${esc(r.nama_barang)}`,
                    r.stok_sistem, r.stok_fisik,
                    `<b class="${cls}">${r.selisih > 0 ? '+' : ''}${r.selisih}</b>`,
                    esc(r.keterangan || '-'), esc(r.user ? r.user.name : '-')
                ]);
            });
            table.draw(false);
        });
    }
    loadTable();

    function hitung() {
        const f = $('#adj_fisik').val();
        if (sistem === null || f === '') { $('#adj_selisih').text('-').attr('class', ''); return; }
        const s = parseInt(f, 10) - sistem;
        $('#adj_selisih').text((s > 0 ? '+' : '') + s).attr('class', s > 0 ? 'trx-in' : (s < 0 ? 'trx-out' : ''));
    }
    $('#adj_fisik').on('input', hitung);

    function pilih(kode) {
        $('.alert-danger').addClass('d-none');
        if (!kode) { sistem = null; $('#adj_sistem').val(''); hitung(); return; }
        $.getJSON('/barang/by-kode', { kode: kode }).done(function (res) {
            sistem = parseInt(res.data.stok || 0, 10);
            $('#adj_sistem').val(sistem + ' ' + (res.data.satuan ? res.data.satuan.satuan : ''));
            $('#adj_barang').val(res.data.kode_barang).trigger('change.select2');
            $('#adj_scan').val(res.data.kode_barang);
            StockuScan.beep();
            $('#adj_fisik').trigger('focus').select();
            hitung();
        }).fail(function () {
            sistem = null; $('#adj_sistem').val('');
            $('#alert-kode_barang').removeClass('d-none').text('Kode "' + kode + '" tidak terdaftar.');
        });
    }
    $('#adj_barang').on('change', function () { pilih($(this).val()); });
    StockuScan.bindInput('#adj_scan', pilih);
    $('#adj_camera').on('click', function () { StockuScan.open(pilih); });
    $('#adj_fisik').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#adj_simpan').click(); } });

    $('#adj_simpan').on('click', function () {
        $('.alert-danger').addClass('d-none');
        $.ajax({
            url: '/penyesuaian-stok', type: 'POST',
            data: { _token: $("meta[name='csrf-token']").attr('content'), tanggal: $('#adj_tanggal').val(), kode_barang: $('#adj_barang').val(),
                    stok_fisik: $('#adj_fisik').val(), keterangan: $('#adj_ket').val() },
            success: function (res) {
                Swal.fire({ icon: 'success', title: res.message, timer: 1800, showConfirmButton: false });
                $('#adj_fisik, #adj_ket, #adj_scan, #adj_sistem').val('');
                $('#adj_barang').val('').trigger('change.select2'); sistem = null; hitung();
                loadTable();
                $('#adj_scan').trigger('focus');
            },
            error: function (xhr) {
                const e = xhr.responseJSON || {};
                ['tanggal', 'kode_barang', 'stok_fisik'].forEach(function (f) { if (e[f] && e[f][0]) $('#alert-' + f).removeClass('d-none').text(e[f][0]); });
            }
        });
    });
});
</script>
@endsection
