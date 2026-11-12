@extends('layouts.app')

@section('modals')
<div class="modal fade" id="modal_absen" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ubah Kehadiran — <span id="m_nama"></span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="m_user">
        <div class="form-group">
          <label>Status</label>
          <select id="m_status" class="form-control">
            <option value="hadir">Hadir</option>
            <option value="terlambat">Terlambat</option>
            <option value="izin">Izin</option>
            <option value="sakit">Sakit</option>
            <option value="alpa">Alpa</option>
          </select>
          <div class="alert alert-danger mt-2 d-none" id="alert-status"></div>
        </div>
        <div class="form-row" id="m_jam_row">
          <div class="form-group col-6"><label>Jam Masuk</label><input type="time" id="m_jam_masuk" class="form-control"></div>
          <div class="form-group col-6"><label>Jam Pulang</label><input type="time" id="m_jam_pulang" class="form-control"></div>
        </div>
        <div class="form-group mb-0">
          <label>Keterangan</label>
          <input type="text" id="m_ket" class="form-control" maxlength="255" placeholder="Opsional">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="m_simpan">Simpan</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('content')
<div class="section-header">
  <h1>Kelola Absensi</h1>
  <div class="ml-auto header-actions">
    <label for="abs_tanggal" class="mb-0 mr-1 text-muted small">Tanggal</label>
    <input type="date" id="abs_tanggal" class="form-control" style="width:auto;height:36px">
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:14px">
      <div>
        <div class="text-muted small">Status sesi absensi</div>
        <div class="d-flex align-items-center" style="gap:10px">
          <span id="sesi_badge" class="badge badge-abs badge-abs-belum">Belum dibuka</span>
          <span id="sesi_info" class="text-muted small"></span>
        </div>
      </div>
      <div class="header-actions">
        <button type="button" class="btn btn-primary" id="btn_buka"><i class="fas fa-door-open"></i> Buka Absensi</button>
        <button type="button" class="btn btn-danger d-none" id="btn_tutup"><i class="fas fa-lock"></i> Tutup Absensi</button>
      </div>
    </div>

    <div id="form_buka" class="mt-3 d-none">
      <hr>
      <div class="form-row">
        <div class="form-group col-md-3">
          <label>Batas Terlambat</label>
          <input type="time" id="f_batas" class="form-control" value="08:00">
          <div class="alert alert-danger mt-2 d-none" id="alert-batas_terlambat"></div>
        </div>
        <div class="form-group col-md-3">
          <label>Jam Tutup Otomatis <small class="text-muted">(opsional)</small></label>
          <input type="time" id="f_tutup" class="form-control" value="17:00">
          <div class="alert alert-danger mt-2 d-none" id="alert-jam_tutup"></div>
        </div>
        <div class="form-group col-md-4">
          <label>Catatan <small class="text-muted">(opsional)</small></label>
          <input type="text" id="f_catatan" class="form-control" maxlength="255" placeholder="mis. Apel pagi jam 07.45">
        </div>
        <div class="form-group col-md-2 d-flex align-items-end">
          <button type="button" class="btn btn-primary btn-block" id="btn_buka_ok"><i class="fas fa-check"></i> Buka</button>
        </div>
      </div>
      <small class="text-muted">Setelah dibuka, Admin Gudang dan Kepala Gudang dapat absen dari menu "Absen Saya".</small>
    </div>
  </div>
</div>

<div class="card kpi-strip kpi-wide">
  <div class="kpi"><div class="kpi-label"><span>Hadir</span></div><div class="kpi-value" id="k_hadir">0</div></div>
  <div class="kpi"><div class="kpi-label"><span>Terlambat</span></div><div class="kpi-value" id="k_terlambat">0</div></div>
  <div class="kpi"><div class="kpi-label"><span>Izin</span></div><div class="kpi-value" id="k_izin">0</div></div>
  <div class="kpi"><div class="kpi-label"><span>Sakit</span></div><div class="kpi-value" id="k_sakit">0</div></div>
  <div class="kpi"><div class="kpi-label"><span>Alpa</span></div><div class="kpi-value" id="k_alpa">0</div></div>
  <div class="kpi"><div class="kpi-label"><span>Belum Absen</span></div><div class="kpi-value" id="k_belum">0</div></div>
</div>

<div class="card">
  <div class="card-header"><h4>Daftar Kehadiran</h4></div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="table_absen" class="display">
        <thead><tr><th>Nama</th><th>Jabatan</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Keterangan</th><th>Opsi</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header split">
    <h4>Rekap Bulanan <small class="text-muted ml-2" id="rekap_info"></small></h4>
    <input type="month" id="rekap_bulan" class="form-control" style="width:auto;height:36px">
  </div>
  <div class="card-body">
    <div class="chart-box sm mb-3"><canvas id="chartRekap" role="img" aria-label="Rekap kehadiran per pegawai"></canvas></div>
    <div class="table-responsive">
      <table id="table_rekap" class="display">
        <thead><tr><th>Nama</th><th>Hadir</th><th>Terlambat</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>% Kehadiran</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="{{ asset('assets/js/stocku-charts.js') }}?v=1"></script>
<script>
$(function () {
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const token = () => $("meta[name='csrf-token']").attr("content");
  const LABEL = { hadir: 'Hadir', terlambat: 'Terlambat', izin: 'Izin', sakit: 'Sakit', alpa: 'Alpa' };
  const tbl = $('#table_absen').DataTable({ paging: true, order: [] });
  const tblRekap = $('#table_rekap').DataTable({ paging: true, order: [] });
  let sesi = null, rows = [], chart = null;

  function p(n) { return String(n).padStart(2, '0'); }
  const d = new Date();
  $('#abs_tanggal').val(d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()));
  $('#rekap_bulan').val(d.getFullYear() + '-' + p(d.getMonth() + 1));

  function badge(s) {
    return s ? `<span class="badge badge-abs badge-abs-${s}">${LABEL[s]}</span>` : '<span class="badge badge-abs badge-abs-belum">Belum absen</span>';
  }

  function load() {
    $.getJSON('/absensi/get-data', { tanggal: $('#abs_tanggal').val() }, function (r) {
      sesi = r.sesi; rows = r.rows;
      ['hadir', 'terlambat', 'izin', 'sakit', 'alpa', 'belum'].forEach(function (k) { $('#k_' + k).text(r.ringkasan[k] || 0); });

      const $b = $('#sesi_badge');
      if (!sesi) {
        $b.attr('class', 'badge badge-abs badge-abs-belum').text('Belum dibuka'); $('#sesi_info').text('');
        $('#btn_buka').removeClass('d-none').html('<i class="fas fa-door-open"></i> Buka Absensi'); $('#btn_tutup').addClass('d-none');
      } else if (sesi.status === 'buka') {
        $b.attr('class', 'badge badge-abs badge-abs-hadir').text('Dibuka');
        $('#sesi_info').text('Sejak ' + sesi.jam_buka + ' · terlambat setelah ' + sesi.batas_terlambat + (sesi.jam_tutup ? ' · tutup ' + sesi.jam_tutup : '') + (sesi.catatan ? ' · ' + sesi.catatan : ''));
        $('#btn_buka').addClass('d-none'); $('#btn_tutup').removeClass('d-none');
      } else {
        $b.attr('class', 'badge badge-abs badge-abs-alpa').text('Ditutup'); $('#sesi_info').text('Dibuka ' + sesi.jam_buka);
        $('#btn_buka').removeClass('d-none').html('<i class="fas fa-redo"></i> Buka Kembali'); $('#btn_tutup').addClass('d-none');
      }
      if (sesi && sesi.status === 'buka') $('#form_buka').addClass('d-none');

      tbl.clear();
      rows.forEach(function (x) {
        const aksi = sesi
          ? `<div class="row-actions"><a href="javascript:void(0)" class="btn btn-icon btn-lg btn-warning btn-ubah" data-id="${x.user_id}" title="Ubah kehadiran"><i class="far fa-edit"></i></a></div>`
          : '<span class="text-muted small">Buka absensi dulu</span>';
        tbl.row.add([esc(x.nama), esc(x.role), badge(x.status), x.jam_masuk || '-', x.jam_pulang || '-', esc(x.keterangan || '-'), aksi]);
      });
      tbl.draw(false);
    });
  }

  function loadRekap() {
    $.getJSON('/absensi/rekap', { bulan: $('#rekap_bulan').val() }, function (r) {
      $('#rekap_info').text(r.total_sesi + ' hari absensi');
      tblRekap.clear();
      r.rows.forEach(function (x) {
        tblRekap.row.add([esc(x.nama), x.hadir, x.terlambat, x.izin, x.sakit, x.alpa, x.persen + '%']);
      });
      tblRekap.draw(false);

      if (chart) { chart.destroy(); chart = null; }
      const cols = { hadir: '#3fb950', terlambat: '#e0a13a', izin: '#4a8cff', sakit: '#a371f7', alpa: '#f0615a' };
      chart = StockuCharts.make(document.getElementById('chartRekap'), {
        type: 'bar',
        data: { labels: r.rows.map((x) => x.nama), datasets: Object.keys(cols).map(function (k) {
          return { label: LABEL[k], data: r.rows.map((x) => x[k]), semantic: cols[k], borderRadius: 2, maxBarThickness: 36 };
        }) },
        options: {
          interaction: { mode: 'index', intersect: false },
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
          scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, border: { display: false } } }
        }
      });
    });
  }

  load(); loadRekap();
  $('#abs_tanggal').on('change', load);
  $('#rekap_bulan').on('change', loadRekap);

  // ---- buka / tutup ----
  $('#btn_buka').on('click', function () {
    if (sesi) { $('#f_batas').val(sesi.batas_terlambat || '08:00'); $('#f_tutup').val(sesi.jam_tutup || ''); $('#f_catatan').val(sesi.catatan || ''); }
    $('#form_buka').toggleClass('d-none');
  });
  $('#btn_buka_ok').on('click', function () {
    $('#form_buka .alert-danger').addClass('d-none');
    $.post('/absensi/buka', { _token: token(), tanggal: $('#abs_tanggal').val(), batas_terlambat: $('#f_batas').val(), jam_tutup: $('#f_tutup').val(), catatan: $('#f_catatan').val() })
      .done(function (r) { Swal.fire({ icon: 'success', title: r.message, timer: 2200, showConfirmButton: false }); $('#form_buka').addClass('d-none'); load(); loadRekap(); })
      .fail(function (xhr) {
        const e = xhr.responseJSON || {};
        ['batas_terlambat', 'jam_tutup'].forEach(function (f) { if (e[f]) $('#alert-' + f).removeClass('d-none').text(e[f][0]); });
      });
  });
  $('#btn_tutup').on('click', function () {
    Swal.fire({ title: 'Tutup absensi?', text: 'Pegawai yang belum absen akan dicatat alpa.', icon: 'warning', showCancelButton: true, cancelButtonText: 'BATAL', confirmButtonText: 'YA, TUTUP' })
      .then(function (res) {
        if (!res.isConfirmed) return;
        $.post('/absensi/' + sesi.id + '/tutup', { _token: token() }).done(function (r) {
          Swal.fire({ icon: 'success', title: r.message, timer: 2200, showConfirmButton: false }); load(); loadRekap();
        });
      });
  });

  // ---- ubah manual ----
  function toggleJam() { $('#m_jam_row').toggleClass('d-none', ['izin', 'sakit', 'alpa'].indexOf($('#m_status').val()) > -1); }
  $('#m_status').on('change', toggleJam);
  $('body').on('click', '.btn-ubah', function () {
    const x = rows.filter((r) => r.user_id === $(this).data('id'))[0];
    $('#modal_absen .alert-danger').addClass('d-none');
    $('#m_user').val(x.user_id); $('#m_nama').text(x.nama); $('#m_status').val(x.status || 'hadir');
    $('#m_jam_masuk').val(x.jam_masuk || ''); $('#m_jam_pulang').val(x.jam_pulang || ''); $('#m_ket').val(x.keterangan || '');
    toggleJam(); $('#modal_absen').modal('show');
  });
  $('#m_simpan').on('click', function () {
    $.post('/absensi/' + sesi.id + '/set', { _token: token(), user_id: $('#m_user').val(), status: $('#m_status').val(), jam_masuk: $('#m_jam_masuk').val(), jam_pulang: $('#m_jam_pulang').val(), keterangan: $('#m_ket').val() })
      .done(function (r) { $('#modal_absen').modal('hide'); Swal.fire({ icon: 'success', title: r.message, timer: 1500, showConfirmButton: false }); load(); loadRekap(); })
      .fail(function (xhr) { const e = xhr.responseJSON || {}; if (e.status) $('#alert-status').removeClass('d-none').text(e.status[0]); });
  });
});
</script>
@endpush
