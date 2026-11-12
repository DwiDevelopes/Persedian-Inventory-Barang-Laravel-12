@extends('layouts.app')

@section('content')
<div class="section-header">
  <h1>Absen Saya</h1>
</div>

<div class="row">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-body text-center">
        <div class="text-muted small" id="as_tanggal"></div>
        <div class="abs-clock my-2" id="as_jam">--:--:--</div>
        <div class="mb-3"><span id="as_badge" class="badge badge-abs badge-abs-belum">Memuat…</span></div>
        <div id="as_info" class="text-muted small mb-3"></div>

        <div class="abs-actions">
          <button type="button" class="btn btn-primary" id="as_masuk" disabled><i class="fas fa-sign-in-alt"></i> Absen Masuk</button>
          <button type="button" class="btn btn-outline-primary" id="as_pulang" disabled><i class="fas fa-sign-out-alt"></i> Absen Pulang</button>
        </div>
        <div class="alert alert-danger mt-3 mb-0 d-none" id="as_error" role="alert"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><h4>Riwayat Absensi Saya</h4></div>
      <div class="card-body">
        <div class="table-responsive">
          <table id="table_saya" class="display">
            <thead><tr><th>Tanggal</th><th>Status</th><th>Masuk</th><th>Pulang</th><th>Keterangan</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const LABEL = { hadir: 'Hadir', terlambat: 'Terlambat', izin: 'Izin', sakit: 'Sakit', alpa: 'Alpa' };
  const tbl = $('#table_saya').DataTable({ paging: true, order: [] });
  const token = () => $("meta[name='csrf-token']").attr("content");
  const badge = (s) => `<span class="badge badge-abs badge-abs-${s}">${LABEL[s]}</span>`;

  // jam berjalan (dibersihkan otomatis saat halaman diganti karena elemennya hilang)
  function tick() {
    const el = document.getElementById('as_jam');
    if (!el) { clearInterval(timer); return; }
    const d = new Date();
    el.textContent = d.toLocaleTimeString('id-ID', { hour12: false });
    document.getElementById('as_tanggal').textContent = d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  }
  const timer = setInterval(tick, 1000); tick();

  function load() {
    $.getJSON('/absen-saya/status', function (r) {
      const $b = $('#as_badge'), saya = r.saya;
      $('#as_error').addClass('d-none');

      if (saya) {
        $b.attr('class', 'badge badge-abs badge-abs-' + saya.status).text(LABEL[saya.status]);
        $('#as_info').text(saya.jam_masuk ? 'Masuk ' + saya.jam_masuk + (saya.jam_pulang ? ' · Pulang ' + saya.jam_pulang : '') : (saya.keterangan || ''));
      } else if (r.terbuka) {
        $b.attr('class', 'badge badge-abs badge-abs-hadir').text('Absensi dibuka');
        $('#as_info').text('Batas terlambat ' + r.sesi.batas_terlambat + (r.sesi.jam_tutup ? ' · tutup ' + r.sesi.jam_tutup : '') + (r.sesi.catatan ? ' · ' + r.sesi.catatan : ''));
      } else {
        $b.attr('class', 'badge badge-abs badge-abs-belum').text(r.sesi ? 'Absensi ditutup' : 'Belum dibuka');
        $('#as_info').text('Menunggu Super Admin membuka absensi hari ini.');
      }

      $('#as_masuk').prop('disabled', !(r.terbuka && !saya));
      $('#as_pulang').prop('disabled', !(saya && saya.jam_masuk && !saya.jam_pulang));

      tbl.clear();
      r.riwayat.forEach(function (x) { tbl.row.add([esc(x.tanggal), badge(x.status), x.jam_masuk || '-', x.jam_pulang || '-', esc(x.keterangan || '-')]); });
      tbl.draw(false);
    });
  }

  function aksi(url) {
    $.post(url, { _token: token() })
      .done(function (r) { Swal.fire({ icon: 'success', title: r.message, timer: 2000, showConfirmButton: false }); load(); })
      .fail(function (xhr) { $('#as_error').removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menyimpan.'); load(); });
  }
  $('#as_masuk').on('click', function () { aksi('/absen-saya/masuk'); });
  $('#as_pulang').on('click', function () { aksi('/absen-saya/pulang'); });
  load();
});
</script>
@endpush
