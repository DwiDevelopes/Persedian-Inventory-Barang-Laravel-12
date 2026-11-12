@extends('layouts.app')

@section('content')
<div class="section-header">
    <h1>Scan Barcode</h1>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="btn-group d-flex mb-3 scan-modes" id="scan-mode" role="group" aria-label="Mode scan">
                    <button type="button" class="btn btn-primary active" data-mode="cek"><i class="fas fa-search"></i> Cek Stok</button>
                    <button type="button" class="btn btn-outline-primary" data-mode="masuk"><i class="fas fa-arrow-down"></i> Masuk</button>
                    <button type="button" class="btn btn-outline-primary" data-mode="keluar"><i class="fas fa-arrow-up"></i> Keluar</button>
                </div>

                <div class="scan-hero">
                    <div class="big"><i class="fas fa-barcode"></i></div>
                    <p class="mb-2 mt-2">Arahkan scanner barcode ke kolom di bawah, atau gunakan kamera.</p>
                    <div class="input-group scan-input-group scan-lg">
                        <input type="text" id="scan-input" class="form-control" placeholder="Scan / ketik kode lalu Enter" autocomplete="off" autofocus>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" id="scan-camera" title="Scan dengan kamera"><i class="fas fa-camera"></i></button>
                        </div>
                    </div>
                    <div id="scan-msg" class="small mt-2" aria-live="polite"></div>
                </div>

                <h6 class="mt-4 mb-2">Riwayat scan sesi ini</h6>
                <ul class="list-unstyled mb-0" id="scan-log"><li class="text-muted small">Belum ada scan.</li></ul>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card scan-result d-none" id="scan-result">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-4 text-center mb-3">
                        <img id="r-gambar" src="" alt="" class="img-fluid" style="border-radius:8px;max-height:150px;object-fit:cover">
                        <div class="mt-2"><svg id="r-barcode"></svg></div>
                    </div>
                    <div class="col-sm-8">
                        <h4 class="mb-1" id="r-nama"></h4>
                        <div class="mb-2"><span class="kode-badge" id="r-kode"></span> <span class="text-muted small" id="r-jenis"></span></div>
                        <div class="d-flex align-items-end mb-2">
                            <div class="stok-besar" id="r-stok">0</div>
                            <div class="ml-2 pb-1"><span id="r-satuan"></span> <span class="badge" id="r-status"></span></div>
                        </div>
                        <div class="small text-muted">Stok minimum: <b id="r-min"></b> &middot; Lokasi: <b id="r-lokasi"></b></div>
                        <div class="small text-muted">Harga beli: <b id="r-hb"></b> &middot; Harga jual: <b id="r-hj"></b></div>
                    </div>
                </div>

                <div id="r-aksi" class="d-none">
                    <hr>
                    <div class="form-row">
                        <div class="form-group col-sm-4">
                            <label for="r-qty">Jumlah <span id="r-qty-satuan"></span></label>
                            <input type="number" min="1" step="1" value="1" class="form-control" id="r-qty">
                        </div>
                        <div class="form-group col-sm-5">
                            <label for="r-pihak" id="r-pihak-label">Supplier</label>
                            <select id="r-pihak" class="form-control"></select>
                        </div>
                        <div class="form-group col-sm-3 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-block" id="r-simpan"><i class="fas fa-check"></i> Simpan</button>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none" id="r-error" role="alert"></div>
                    <small class="text-muted">Tip: tekan Enter pada kolom jumlah untuk menyimpan, lalu langsung scan barang berikutnya.</small>
                </div>

                <hr>
                <h6>Transaksi terakhir barang ini</h6>
                <div id="r-riwayat" class="small"></div>
                <a id="r-kartu" href="#" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-stream"></i> Lihat Kartu Stok</a>
            </div>
        </div>

        <div class="card" id="scan-empty">
            <div class="card-body"><div class="empty-state"><i class="fas fa-qrcode"></i>Hasil scan akan tampil di sini.</div></div>
        </div>
    </div>
</div>

<script>
$(function () {
    const SUPPLIERS = @json($suppliers->map(fn ($s) => ['id' => $s->id, 'nama' => $s->supplier])->values());
    const CUSTOMERS = @json($customers->map(fn ($c) => ['id' => $c->id, 'nama' => $c->customer])->values());
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const rp = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
    let mode = 'cek', current = null;

    function setMsg(text, kind) {
        $('#scan-msg').attr('class', 'small mt-2 ' + (kind === 'err' ? 'text-danger' : 'text-success')).text(text || '');
    }

    function fillPihak() {
        const list = mode === 'masuk' ? SUPPLIERS : CUSTOMERS;
        $('#r-pihak-label').text(mode === 'masuk' ? 'Supplier' : 'Customer');
        $('#r-pihak').html(list.map((p) => `<option value="${p.id}">${esc(p.nama)}</option>`).join(''));
    }

    function applyMode() {
        $('#scan-mode button').each(function () {
            const on = $(this).data('mode') === mode;
            $(this).toggleClass('active btn-primary', on).toggleClass('btn-outline-primary', !on);
        });
        fillPihak();
        $('#r-aksi').toggleClass('d-none', mode === 'cek' || !current);
        $('#scan-input').trigger('focus');
    }

    $('body').on('click', '#scan-mode button', function () { mode = $(this).data('mode'); applyMode(); });

    function render(res, silent) {
        const d = res.data; current = d;
        $('#scan-empty').addClass('d-none'); $('#scan-result').removeClass('d-none');
        $('#r-gambar').attr('src', '/storage/' + d.gambar);
        $('#r-nama').text(d.nama_barang); $('#r-kode').text(d.kode_barang); $('#r-jenis').text(d.jenis);
        $('#r-stok').text(d.stok); $('#r-satuan').text(d.satuan); $('#r-qty-satuan').text(d.satuan ? '(' + d.satuan + ')' : '');
        $('#r-min').text(d.stok_minimum); $('#r-lokasi').text(d.lokasi || '-');
        $('#r-hb').text(rp(d.harga_beli)); $('#r-hj').text(rp(d.harga_jual));
        $('#r-status').attr('class', 'badge badge-status-' + d.status).text({aman: 'Aman', menipis: 'Menipis', habis: 'Habis'}[d.status]);
        $('#r-kartu').attr('href', '/kartu-stok?kode=' + encodeURIComponent(d.kode_barang)).attr('data-nav', '');
        StockuScan.barcode($('#r-barcode')[0], d.kode_barang, { height: 40, fontSize: 12 });

        $('#r-riwayat').html(res.riwayat.length ? res.riwayat.map((t) =>
            `<div class="d-flex justify-content-between border-bottom py-1"><span>${esc(t.tanggal)} · ${esc(t.kode)}</span><b class="${t.jenis === 'masuk' ? 'trx-in' : 'trx-out'}">${t.jenis === 'masuk' ? '+' : '−'}${t.jumlah}</b></div>`
        ).join('') : '<span class="text-muted">Belum ada transaksi.</span>');

        $('#r-error').addClass('d-none');
        $('#r-aksi').toggleClass('d-none', mode === 'cek');
        if (mode !== 'cek') { $('#r-qty').val(1); if (!silent) { $('#r-qty').trigger('focus').select(); } }
    }

    function logScan(text, ok) {
        const $ul = $('#scan-log');
        if ($ul.find('.text-muted').length === 1 && $ul.children().length === 1) $ul.empty();
        const t = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        $ul.prepend(`<li class="small py-1 border-bottom ${ok ? '' : 'text-danger'}"><span class="text-muted">${t}</span> ${esc(text)}</li>`);
        $ul.children().slice(8).remove();
    }

    function cari(kode, silent) {
        $.getJSON('/scan/lookup', { kode: kode }).done(function (res) {
            if (!silent) {
                StockuScan.beep(); setMsg('Ditemukan: ' + res.data.nama_barang, 'ok');
                logScan(res.data.kode_barang + ' — ' + res.data.nama_barang, true);
            }
            render(res, silent);
        }).fail(function (xhr) {
            setMsg((xhr.responseJSON && xhr.responseJSON.message) || 'Barang tidak ditemukan.', 'err');
            logScan(kode + ' — tidak terdaftar', false);
        });
    }
    StockuScan.bindInput('#scan-input', function (k) { cari(k); });
    $('#scan-camera').on('click', function () { StockuScan.open(function (k) { cari(k); }); });

    function simpan() {
        if (!current || mode === 'cek') return;
        const d = new Date(), p = (n) => String(n).padStart(2, '0');
        const tgl = d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
        const kode = (mode === 'masuk' ? 'TRX-IN-' : 'TRX-OUT-') + tgl + '-' + p(d.getHours()) + p(d.getMinutes()) + p(d.getSeconds());
        const data = { _token: $("meta[name='csrf-token']").attr("content"), kode_transaksi: kode, kode_barang: current.kode_barang, nama_barang: current.nama_barang };
        if (mode === 'masuk') { data.tanggal_masuk = tgl; data.jumlah_masuk = $('#r-qty').val(); data.supplier_id = $('#r-pihak').val(); }
        else { data.tanggal_keluar = tgl; data.jumlah_keluar = $('#r-qty').val(); data.customer_id = $('#r-pihak').val(); }

        $('#r-simpan').prop('disabled', true);
        $.ajax({ url: mode === 'masuk' ? '/barang-masuk' : '/barang-keluar', type: 'POST', data: data })
            .done(function () {
                StockuScan.beep();
                logScan((mode === 'masuk' ? '+' : '−') + data[mode === 'masuk' ? 'jumlah_masuk' : 'jumlah_keluar'] + ' ' + current.nama_barang, true);
                setMsg('Tersimpan. Scan barang berikutnya.', 'ok');
                $('#scan-input').val('').trigger('focus');
                cari(current.kode_barang, true);   // segarkan stok
            })
            .fail(function (xhr) {
                const e = xhr.responseJSON || {};
                const first = Object.keys(e).map((k) => e[k][0]).filter(Boolean)[0] || 'Gagal menyimpan.';
                $('#r-error').removeClass('d-none').text(first);
            })
            .always(function () { $('#r-simpan').prop('disabled', false); });
    }
    $('#r-simpan').on('click', simpan);
    $('#r-qty').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); simpan(); } });

    applyMode();
    setTimeout(function () { $('#scan-input').trigger('focus'); }, 200);
});
</script>
@endsection
