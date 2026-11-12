@extends('layouts.app')

@section('modals')
@include('barang.create')
@include('barang.edit')
@include('barang.show')
@endsection


@section('content')
    <div class="section-header">
        <h1>Data Barang</h1>
        <div class="ml-auto header-actions">
            <a href="javascript:void(0)" class="btn btn-outline-primary" id="button_scan_cari"><i class="fas fa-barcode"></i> Scan Cari</a>
            <a href="/barang/label" class="btn btn-outline-primary"><i class="fas fa-print"></i> Label Barcode</a>
            <a href="javascript:void(0)" class="btn btn-primary" id="button_tambah_barang"><i class="fa fa-plus"></i> Tambah
                Barang</a>
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
                                    <th>Gambar</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Jenis</th>
                                    <th>Lokasi</th>
                                    <th>Stok</th>
                                    <th>Harga Beli</th>
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
        const FIELDS = ['gambar', 'kode_barang', 'nama_barang', 'stok_minimum', 'stok', 'jenis_id', 'satuan_id', 'lokasi', 'harga_beli', 'harga_jual', 'deskripsi'];
        const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const rp  = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
        const table = $('#table_id').DataTable({ paging: true });
        const token = () => $("meta[name='csrf-token']").attr("content");

        function statusBadge(b) {
            const stok = Number(b.stok || 0), min = Number(b.stok_minimum || 0);
            const st = stok <= 0 ? 'habis' : (stok <= min ? 'menipis' : 'aman');
            const txt = stok <= 0 ? 'Habis' : (stok <= min ? 'Menipis' : 'Aman');
            return `<span class="badge badge-status-${st}">${txt}</span>`;
        }

        function loadTable() {
            $.getJSON('/barang/get-data', function (response) {
                table.clear();
                response.data.forEach(function (v, i) {
                    const satuan = v.satuan ? v.satuan.satuan : '';
                    table.row.add($(`
                        <tr class="barang-row" id="index_${v.id}">
                            <td>${i + 1}</td>
                            <td><img src="/storage/${esc(v.gambar)}" alt="gambar Barang" style="width: 70px; height: 70px; object-fit: cover; border-radius: 6px"></td>
                            <td><span class="kode-badge">${esc(v.kode_barang)}</span></td>
                            <td>${esc(v.nama_barang)}</td>
                            <td>${esc(v.jenis ? v.jenis.jenis_barang : '-')}</td>
                            <td>${esc(v.lokasi || '-')}</td>
                            <td>${Number(v.stok || 0)} ${esc(satuan)} ${statusBadge(v)}</td>
                            <td>${rp(v.harga_beli)}</td>
                            <td><div class="row-actions">
                                    <a href="javascript:void(0)" id="button_detail_barang" data-id="${v.id}" class="btn btn-icon btn-lg btn-success" title="Detail &amp; barcode"><i class="far fa-eye"></i></a>
                                    <a href="javascript:void(0)" id="button_edit_barang" data-id="${v.id}" class="btn btn-icon btn-lg btn-warning" title="Edit"><i class="far fa-edit"></i></a>
                                    <a href="/barang/label?ids[]=${v.id}" class="btn btn-icon btn-lg btn-info" title="Cetak label"><i class="fas fa-print"></i></a>
                                    <a href="javascript:void(0)" id="button_hapus_barang" data-id="${v.id}" class="btn btn-icon btn-lg btn-danger" title="Hapus"><i class="fas fa-trash"></i></a>
                                </div></td>
                        </tr>`));
                });
                table.draw(false);
            });
        }
        loadTable();

        function showErrors(modal, errors) {
            $(modal + ' .alert-danger').addClass('d-none').removeClass('d-block');
            if (!errors) return;
            FIELDS.forEach(function (f) {
                if (errors[f] && errors[f][0]) {
                    $(modal + ' #alert-' + f).removeClass('d-none').addClass('d-block').html(esc(errors[f][0]));
                }
            });
        }

        // ---- Scan: cari barang di tabel / isi kolom kode ----
        $('body').on('click', '#button_scan_cari', function () {
            StockuScan.open(function (kode) { table.search(kode).draw(); });
        });
        $('body').on('click', '.btn-scan-kode', function () {
            const target = $(this).data('target');
            StockuScan.open(function (kode) { $(target).val(kode).trigger('input'); });
        });

        // ---- Tambah ----
        $('body').on('click', '#button_tambah_barang', function () {
            showErrors('#modal_tambah_barang');
            $('#modal_tambah_barang').modal('show');
        });

        $('#store').click(function (e) {
            e.preventDefault();
            showErrors('#modal_tambah_barang');

            let formData = new FormData();
            formData.append('gambar', $('#gambar')[0].files[0]);
            ['kode_barang', 'nama_barang', 'stok_minimum', 'stok', 'jenis_id', 'satuan_id', 'lokasi', 'harga_beli', 'harga_jual', 'deskripsi'].forEach(function (f) {
                formData.append(f, $('#modal_tambah_barang #' + f).val());
            });
            formData.append('_token', token());

            $.ajax({
                url: '/barang', type: "POST", cache: false, data: formData, contentType: false, processData: false,
                success: function (response) {
                    Swal.fire({ icon: 'success', title: `${response.message}`, text: 'Kode barang: ' + response.data.kode_barang, showConfirmButton: true, timer: 3000 });
                    loadTable();
                    ['kode_barang', 'nama_barang', 'stok_minimum', 'lokasi', 'deskripsi'].forEach(function (f) { $('#modal_tambah_barang #' + f).val(''); });
                    $('#modal_tambah_barang #stok, #modal_tambah_barang #harga_beli, #modal_tambah_barang #harga_jual').val('0');
                    $('#modal_tambah_barang').modal('hide');
                },
                error: function (error) { showErrors('#modal_tambah_barang', error.responseJSON); }
            });
        });

        // ---- Detail + barcode ----
        $('body').on('click', '#button_detail_barang', function () {
            $.getJSON(`/barang/${$(this).data('id')}/`, function (response) {
                const d = response.data;
                $('#barang_id').val(d.id);
                $('#detail_kode_barang').val(d.kode_barang);
                $('#detail_nama_barang').val(d.nama_barang);
                $('#detail_jenis_id').val(d.jenis_id);
                $('#detail_satuan_id').val(d.satuan_id);
                $('#detail_stok').val(d.stok !== null && d.stok !== '' ? d.stok : 'Stok Kosong');
                $('#detail_stok_minimum').val(d.stok_minimum);
                $('#detail_lokasi').val(d.lokasi || '-');
                $('#detail_harga_beli').val(rp(d.harga_beli));
                $('#detail_harga_jual').val(rp(d.harga_jual));
                $('#detail_nilai').val(rp(Number(d.stok || 0) * Number(d.harga_beli || 0)));
                $('#detail_deskripsi').val(d.deskripsi);
                $('#detail_gambar_preview').attr('src', '/storage/' + d.gambar);
                StockuScan.barcode($('#detail_barcode')[0], d.kode_barang);
                $('#modal_detail_barang').modal('show');
            });
        });

        // ---- Edit ----
        $('body').on('click', '#button_edit_barang', function () {
            showErrors('#modal_edit_barang');
            $.getJSON(`/barang/${$(this).data('id')}/edit`, function (response) {
                const d = response.data;
                $('#barang_id').val(d.id);
                $('#edit_gambar').val(null);
                $('#edit_kode_barang').val(d.kode_barang);
                $('#edit_nama_barang').val(d.nama_barang);
                $('#edit_stok_minimum').val(d.stok_minimum);
                $('#edit_stok').val(d.stok != null ? d.stok : 0);
                $('#edit_jenis_id').val(d.jenis_id);
                $('#edit_satuan_id').val(d.satuan_id);
                $('#edit_lokasi').val(d.lokasi || '');
                $('#edit_harga_beli').val(d.harga_beli || 0);
                $('#edit_harga_jual').val(d.harga_jual || 0);
                $('#edit_deskripsi').val(d.deskripsi);
                $('#edit_gambar_preview').attr('src', '/storage/' + d.gambar);
                $('#modal_edit_barang').modal('show');
            });
        });

        $('#update').click(function (e) {
            e.preventDefault();
            showErrors('#modal_edit_barang');

            let formData = new FormData();
            formData.append('gambar', $('#edit_gambar')[0].files[0]);
            ['kode_barang', 'nama_barang', 'stok_minimum', 'stok', 'deskripsi', 'jenis_id', 'satuan_id', 'lokasi', 'harga_beli', 'harga_jual'].forEach(function (f) {
                formData.append(f, $('#edit_' + f).val());
            });
            formData.append('_token', token());
            formData.append('_method', 'PUT');

            $.ajax({
                url: `/barang/${$('#barang_id').val()}`, type: "POST", cache: false, data: formData, contentType: false, processData: false,
                success: function (response) {
                    Swal.fire({ icon: 'success', title: `${response.message}`, showConfirmButton: true, timer: 3000 });
                    loadTable();
                    $('#modal_edit_barang').modal('hide');
                },
                error: function (error) { showErrors('#modal_edit_barang', error.responseJSON); }
            });
        });

        // ---- Hapus ----
        $('body').on('click', '#button_hapus_barang', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Apakah Kamu Yakin?', text: "ingin menghapus data ini!", icon: 'warning',
                showCancelButton: true, cancelButtonText: 'TIDAK', confirmButtonText: 'YA, HAPUS!'
            }).then((result) => {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: `/barang/${id}`, type: "DELETE", cache: false, data: { "_token": token() },
                    success: function (response) {
                        Swal.fire({ icon: 'success', title: `${response.message}`, showConfirmButton: true, timer: 3000 });
                        loadTable();
                    }
                });
            });
        });
    });

    // Preview gambar (dipanggil dari atribut onchange di modal)
    function previewImage() { preview.src = URL.createObjectURL(event.target.files[0]); }
    function previewImageEdit() { edit_gambar_preview.src = URL.createObjectURL(event.target.files[0]); }
    </script>
@endsection
