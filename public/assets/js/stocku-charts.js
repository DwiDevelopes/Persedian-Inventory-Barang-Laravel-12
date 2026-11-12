/*
 * Stocku - helper Chart.js: warna mengikuti tema (gelap/terang) dan ikut berganti saat tema diganti.
 * Pakai: StockuCharts.make(canvas, config)
 *  - dataset boleh memakai  cssColor: '--series-1'  agar warna diambil dari variabel CSS tema
 *  - dataset boleh memakai  palette: true            agar tiap batang / irisan memakai warna kategori
 */
(function (w) {
  'use strict';
  var charts = [];
  var PALETTE = ['#4a8cff', '#e0a13a', '#3fb950', '#a371f7', '#f0615a', '#39c5cf', '#ff9bce', '#8b949e'];

  function v(name, fallback) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
  }

  function applyDefaults() {
    Chart.defaults.font.family = v('--font', 'sans-serif');
    Chart.defaults.font.size = 12;
    Chart.defaults.color = v('--muted', '#7f8998');
  }

  function alpha(hex, a) {
    return /^#[0-9a-f]{6}$/i.test(hex) ? hex + a : hex;
  }

  function paint(c) {
    if (!c || !c.canvas || !c.canvas.isConnected) return false;
    var o = c.options, muted = v('--muted', '#7f8998');
    Chart.defaults.color = muted;

    c.data.datasets.forEach(function (ds) {
      var type = ds.type || c.config.type;
      if (ds.cssColor) {
        var col = v(ds.cssColor, '#4a8cff');
        if (type === 'line') { ds.borderColor = col; ds.backgroundColor = ds.fill ? alpha(col, '22') : col; ds.pointBackgroundColor = col; }
        else { ds.backgroundColor = col; }
      }
      if (ds.palette) { ds.backgroundColor = ds.data.map(function (_, i) { return PALETTE[i % PALETTE.length]; }); }
      if (ds.semantic) { ds.backgroundColor = ds.semantic; }
      if (type === 'doughnut' || type === 'pie') { ds.borderColor = v('--raised', '#1f2530'); }
    });

    Object.keys(o.scales || {}).forEach(function (k) {
      var s = o.scales[k];
      s.ticks = s.ticks || {}; s.ticks.color = muted;
      s.grid = s.grid || {}; if (s.grid.display !== false) s.grid.color = v('--chart-grid', 'rgba(255,255,255,.06)');
      s.border = s.border || {}; s.border.color = v('--border-strong', '#353f4f');
      if (s.title) s.title.color = muted;
    });

    o.plugins = o.plugins || {};
    if (o.plugins.legend && o.plugins.legend.labels) o.plugins.legend.labels.color = v('--text-2', '#b0b8c4');
    var t = o.plugins.tooltip = o.plugins.tooltip || {};
    t.backgroundColor = v('--raised', '#1f2530'); t.borderColor = v('--border-strong', '#353f4f'); t.borderWidth = 1;
    t.titleColor = v('--text', '#e4e7ec'); t.bodyColor = v('--text-2', '#b0b8c4');
    t.padding = 10; t.cornerRadius = 6; t.boxPadding = 4;
    c.update('none');
    return true;
  }

  function make(el, config) {
    if (!el || !w.Chart) return null;
    applyDefaults();
    config.options = config.options || {};
    config.options.responsive = true;
    config.options.maintainAspectRatio = false;
    var c = new Chart(el, config);
    charts.push(c);
    paint(c);
    return c;
  }

  // dipanggil app-shell saat tema berganti
  w.__applyChartTheme = function () { charts = charts.filter(paint); };
  w.StockuCharts = { make: make, palette: PALETTE, v: v };
})(window);
