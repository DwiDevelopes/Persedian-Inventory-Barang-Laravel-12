/*
 * Stocku - helper scan barcode & render barcode.
 *  - StockuScan.open(callback)        : buka scanner kamera (html5-qrcode), callback(kode) saat terbaca
 *  - StockuScan.bindInput(el, cb)     : scanner USB/bluetooth (mengetik cepat + Enter) pada sebuah <input>
 *  - StockuScan.barcode(svgEl, kode)  : gambar barcode Code128 (JsBarcode) ke elemen <svg>
 *  - StockuScan.beep()                : bunyi singkat tanda scan berhasil
 * Library pihak ketiga dimuat saat dibutuhkan, jadi halaman lain tidak terbebani.
 */
(function (w, $) {
  'use strict';

  var LIBS = {
    qr: 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js',
    barcode: 'https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js'
  };
  var pending = {};

  function loadLib(key, test, cb) {
    if (test()) return cb(null);
    if (pending[key]) { pending[key].push(cb); return; }
    pending[key] = [cb];
    var s = document.createElement('script');
    s.src = LIBS[key];
    s.onload = function () { var q = pending[key]; delete pending[key]; q.forEach(function (f) { f(null); }); };
    s.onerror = function () { var q = pending[key]; delete pending[key]; q.forEach(function (f) { f(new Error('Gagal memuat library ' + key + '. Periksa koneksi internet.')); }); };
    document.head.appendChild(s);
  }

  var ctx;
  function beep() {
    try {
      ctx = ctx || new (w.AudioContext || w.webkitAudioContext)();
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.type = 'square'; o.frequency.value = 1100; g.gain.value = 0.06;
      o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + 0.09);
    } catch (e) {}
  }

  /* ---------- Scanner kamera ---------- */
  var scanner = null, onResult = null, running = false;

  function stop(done) {
    if (scanner && running) {
      running = false;
      scanner.stop().then(function () { try { scanner.clear(); } catch (e) {} done && done(); })
        .catch(function () { done && done(); });
    } else { done && done(); }
  }

  function start(cameraId) {
    var $err = $('#scanner-error').addClass('d-none').text('');
    var formats = w.Html5QrcodeSupportedFormats ? [
      Html5QrcodeSupportedFormats.CODE_128, Html5QrcodeSupportedFormats.CODE_39,
      Html5QrcodeSupportedFormats.EAN_13, Html5QrcodeSupportedFormats.EAN_8,
      Html5QrcodeSupportedFormats.UPC_A, Html5QrcodeSupportedFormats.UPC_E,
      Html5QrcodeSupportedFormats.ITF, Html5QrcodeSupportedFormats.QR_CODE
    ] : undefined;

    scanner = new Html5Qrcode('scanner-reader', { formatsToSupport: formats, verbose: false,
      experimentalFeatures: { useBarCodeDetectorIfSupported: true } });

    scanner.start(
      cameraId ? { deviceId: { exact: cameraId } } : { facingMode: 'environment' },
      { fps: 10, qrbox: function (vw, vh) { var w2 = Math.min(vw * 0.9, 420); return { width: w2, height: Math.min(vh * 0.6, w2 * 0.5) }; } },
      function (text) {
        if (!running) return;
        beep();
        var cb = onResult;
        stop(function () { $('#scanner-modal').modal('hide'); cb && cb(String(text).trim()); });
      },
      function () { /* frame tanpa barcode - abaikan */ }
    ).then(function () { running = true; })
     .catch(function (e) {
        $err.removeClass('d-none').text(
          (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1')
            ? 'Kamera hanya bisa dipakai lewat HTTPS atau localhost. Gunakan scanner USB / ketik kode manual.'
            : 'Kamera tidak dapat diakses: ' + (e && e.message ? e.message : e));
     });
  }

  function open(cb) {
    onResult = cb;
    $('#scanner-manual').val('');
    $('#scanner-modal').modal('show');
    loadLib('qr', function () { return !!w.Html5Qrcode; }, function (err) {
      if (err) { $('#scanner-error').removeClass('d-none').text(err.message); return; }
      Html5Qrcode.getCameras().then(function (cams) {
        var $sel = $('#scanner-camera').empty();
        cams.forEach(function (c, i) { $sel.append($('<option>').val(c.id).text(c.label || ('Kamera ' + (i + 1)))); });
        $sel.toggleClass('d-none', cams.length < 2);
        // prefer kamera belakang
        var back = cams.filter(function (c) { return /back|rear|belakang|environment/i.test(c.label); })[0];
        if (back) $sel.val(back.id);
        start(cams.length ? $sel.val() : null);
      }).catch(function () { start(null); });
    });
  }

  $(document).on('change', '#scanner-camera', function () { var id = this.value; stop(function () { start(id); }); });
  $(document).on('hidden.bs.modal', '#scanner-modal', function () { stop(); });
  $(document).on('click', '#scanner-manual-ok', function () { submitManual(); });
  $(document).on('keydown', '#scanner-manual', function (e) { if (e.key === 'Enter') { e.preventDefault(); submitManual(); } });
  function submitManual() {
    var v = $.trim($('#scanner-manual').val());
    if (!v) return;
    var cb = onResult;
    stop(function () { $('#scanner-modal').modal('hide'); cb && cb(v); });
  }

  /* ---------- Scanner USB / bluetooth (keyboard wedge) ---------- */
  function bindInput(el, cb) {
    $(el).off('keydown.stockuscan').on('keydown.stockuscan', function (e) {
      if (e.key !== 'Enter') return;
      e.preventDefault();
      var v = $.trim(this.value);
      if (v) { cb(v); this.select(); }
    });
  }

  /* ---------- Render barcode ---------- */
  function barcode(target, kode, opts) {
    loadLib('barcode', function () { return !!w.JsBarcode; }, function (err) {
      if (err) return;
      try {
        JsBarcode(target, kode, $.extend({ format: 'CODE128', width: 2, height: 56, fontSize: 14, margin: 4, displayValue: true }, opts || {}));
      } catch (e) {
        $(target).replaceWith('<span class="text-danger small">Kode tidak valid untuk barcode</span>');
      }
    });
  }

  w.StockuScan = { open: open, bindInput: bindInput, barcode: barcode, beep: beep, loadLib: loadLib };
})(window, window.jQuery);
