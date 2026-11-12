@extends('layouts.app')

@section('content')
<div class="section-header">
    <h1>Label Barcode</h1>
    <div class="ml-auto header-actions">
        <button type="button" class="btn btn-primary" id="btn-print-label"><i class="fas fa-print"></i> Cetak</button>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h4>Pilih Barang</h4></div>
            <div class="card-body">
                <form method="GET" action="/barang/label" data-nav>
                    <div class="form-group">
                        <label>Barang (kosong = semua)</label>
                        <select name="ids[]" class="form-control" multiple size="10" style="min-height:220px">
                            @foreach ($semua as $b)
                                <option value="{{ $b->id }}" @selected(in_array($b->id, $selected))>{{ $b->kode_barang }} — {{ $b->nama_barang }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Tahan Ctrl / Cmd untuk memilih lebih dari satu.</small>
                    </div>
                    <button class="btn btn-outline-primary btn-block" type="submit">Tampilkan Label</button>
                </form>
                <hr>
                <div class="form-group mb-0">
                    <label for="label-copies">Salinan per label</label>
                    <input type="number" id="label-copies" class="form-control" value="1" min="1" max="50">
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h4>Pratinjau ({{ $barangs->count() }} barang)</h4></div>
            <div class="card-body">
                @if ($barangs->isEmpty())
                    <div class="empty-state"><i class="fas fa-barcode"></i>Belum ada barang.</div>
                @endif
                <div id="label-print-area" class="label-sheet">
                    @foreach ($barangs as $b)
                        <div class="label-item" data-kode="{{ $b->kode_barang }}">
                            <div class="nm">{{ $b->nama_barang }}</div>
                            <svg class="label-svg"></svg>
                            @if ($b->lokasi)<div style="font-size:10px">Lokasi: {{ $b->lokasi }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    function render() {
        // gandakan label sesuai jumlah salinan
        var n = Math.max(1, Math.min(50, parseInt($('#label-copies').val(), 10) || 1));
        var $area = $('#label-print-area');
        $area.find('.label-item.copy').remove();
        $area.find('.label-item').each(function () {
            var $orig = $(this);
            for (var i = 1; i < n; i++) { $orig.clone().addClass('copy').appendTo($area); }
        });
        $area.find('.label-item').each(function () {
            var $it = $(this);
            StockuScan.barcode($it.find('svg')[0], $it.data('kode'), { height: 46, fontSize: 12 });
        });
    }
    render();
    $('#label-copies').on('change', render);
    $('#btn-print-label').on('click', function () { window.print(); });
});
</script>
@endsection
