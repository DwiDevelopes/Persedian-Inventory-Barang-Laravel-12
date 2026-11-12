@extends('layouts.app')

@section('modals')
@include('barang-keluar.create')
@endsection


@section('content')
    <div class="section-header">
        <h1>Barang Keluar</h1>
        <div class="ml-auto header-actions">
            <a href="javascript:void(0)" class="btn btn-primary" id="button_tambah_barangKeluar"><i class="fa fa-plus"></i>
                Barang Keluar</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="table_id" class="display">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode Transaksi</th>
                                    <th>Kode Barang</th>
                                    <th>Tanggal Keluar</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah Keluar</th>
                                    <th>Customer</th>
                                    <th>Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(function () {
        const MODAL = '#modal_tambah_barangKeluar';
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const token = () => $("meta[name='csrf-token']").attr("content");
        const table = $('#table_id').DataTable({ paging: true });

        // ---------- tabel ----------
        function loadTable() {
            $.getJSON('/barang-keluar/get-data', function (response) {
                const pihak = {};
                (response.customer || []).forEach(function (p) { pihak[p.id] = p.customer; });
                table.clear();
                response.data.slice().sort((a, b) => b.id - a.id).forEach(function (v, i) {
                    table.row.add($(`
                        <tr class="barang-row" id="index_${v.id}">
                            <td>${i + 1}</td>
                            <td>${esc(v.kode_transaksi)}</td>
                            <td><span class="kode-badge">${esc(v.kode_barang || '-')}</span></td>
                            <td>${esc(v.tanggal_keluar)}</td>
                            <td>${esc(v.nama_barang)}</td>
                            <td>${Number(v.jumlah_keluar)}</td>
                            <td>${esc(pihak[v.customer_id] || '')}</td>
                            <td><div class="row-actions"><a href="javascript:void(0)" id="button_hapus_barangKeluar" data-id="${v.id}" class="btn btn-icon btn-lg btn-danger" title="Hapus"><i class="fas fa-trash"></i></a></div></td>
                        </tr>`));
                });
                table.draw(false);
            });
        }
        loadTable();

        // ---------- kode transaksi & tanggal ----------
        function kodeTransaksi() {
            const d = new Date(), p = (n, l = 2) => String(n).padStart(l, '0');
            return 'TRX-OUT-' + d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + '-' + p(d.getHours()) + p(d.getMinutes()) + p(d.getSeconds());
        }
        function hariIni() {
            const d = new Date(), p = (n) => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate());
        }

        // ---------- pilih barang (manual / scan) ----------
        $('#nama_barang').select2({ dropdownParent: $(MODAL), width: '100%' });

        function clearBarang(msg) {
            $('#kode_barang').val(''); $('#stok').val(''); $('#satuan_id').val('');
            $('#scan_info').removeClass('text-success text-danger').text(msg || 'Arahkan scanner barcode ke kolom ini atau pakai kamera.');
        }

        function applyBarang(b) {
            $('#kode_barang').val(b.kode_barang);
            $('#scan_kode').val(b.kode_barang);
            $('#stok').val(b.stok);
            $('#satuan_id').val(b.satuan || '');
            $('#scan_info').removeClass('text-danger').addClass('text-success').text(b.kode_barang + ' — ' + b.nama_barang + ' (stok ' + b.stok + ' ' + (b.satuan || '') + ')');
        }

        // pilih lewat dropdown
        $('#nama_barang').on('change', function () {
            const nama = $(this).val();
            if (!nama) { clearBarang(); return; }
            $.getJSON('/api/barang-keluar', { nama_barang: nama }).done(applyBarang).fail(function () { clearBarang('Barang tidak ditemukan.'); });
        });

        // pilih lewat scan / ketik kode
        function cariKode(kode) {
            $.getJSON('/api/barang-keluar', { kode_barang: kode }).done(function (b) {
                $('#nama_barang').val(b.nama_barang).trigger('change.select2');
                applyBarang(b);
                $('#jumlah_keluar').trigger('focus');
            }).fail(function () {
                clearBarang('Kode "' + kode + '" tidak terdaftar.');
                $('#scan_info').removeClass('text-success').addClass('text-danger');
                $('#nama_barang').val('').trigger('change.select2');
            });
        }
        StockuScan.bindInput('#scan_kode', cariKode);
        $('#btn_scan_kamera').on('click', function () { StockuScan.open(cariKode); });

        // ---------- modal ----------
        function resetForm() {
            $(MODAL + ' .alert-danger').addClass('d-none').removeClass('d-block');
            $('#kode_transaksi').val(kodeTransaksi());
            $('#tanggal_keluar').val(hariIni());
            $('#jumlah_keluar').val('');
            $('#scan_kode').val('');
            $('#nama_barang').val('').trigger('change.select2');
            clearBarang();
        }
        $('body').on('click', '#button_tambah_barangKeluar', function () { resetForm(); $(MODAL).modal('show'); });
        $(MODAL).on('shown.bs.modal', function () { $('#scan_kode').trigger('focus'); });

        function showErrors(errors) {
            $(MODAL + ' .alert-danger').addClass('d-none').removeClass('d-block');
            ['kode_transaksi', 'tanggal_keluar', 'nama_barang', 'jumlah_keluar', 'customer_id'].forEach(function (f) {
                if (errors && errors[f] && errors[f][0]) { $('#alert-' + f).removeClass('d-none').addClass('d-block').text(errors[f][0]); }
            });
        }

        $('#store').click(function (e) {
            e.preventDefault();
            let formData = new FormData();
            ['kode_transaksi', 'kode_barang', 'tanggal_keluar', 'nama_barang', 'jumlah_keluar', 'customer_id'].forEach(function (f) { formData.append(f, $('#' + f).val() || ''); });
            formData.append('_token', token());

            $.ajax({
                url: '/barang-keluar', type: "POST", cache: false, data: formData, contentType: false, processData: false,
                success: function (response) {
                    Swal.fire({ icon: 'success', title: `${response.message}`, showConfirmButton: false, timer: 1500 });
                    loadTable();
                    StockuScan.beep();
                    if ($('#tetap_buka').is(':checked')) { resetForm(); $('#scan_kode').trigger('focus'); }
                    else { $(MODAL).modal('hide'); }
                },
                error: function (error) { showErrors(error.responseJSON); }
            });
        });

        // ---------- hapus ----------
        $('body').on('click', '#button_hapus_barangKeluar', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Apakah Kamu Yakin?', text: "ingin menghapus data ini ! Stok barang akan dikembalikan.", icon: 'warning',
                showCancelButton: true, cancelButtonText: 'TIDAK', confirmButtonText: 'YA, HAPUS!'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: `/barang-keluar/${id}`, type: "DELETE", cache: false, data: { "_token": token() },
                    success: function (response) {
                        Swal.fire({ icon: 'success', title: `${response.message}`, showConfirmButton: true, timer: 3000 });
                        loadTable();
                    }
                });
            });
        });
    });
    </script>
@endsection
