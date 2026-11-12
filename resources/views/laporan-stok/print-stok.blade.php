<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h1, p { text-align: center; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .r { text-align: right; }
        .c { text-align: center; }
        .footer { position: fixed; bottom: 20px; right: 20px; font-size: 12px; }
    </style>
</head>
<body>
    @php
        $ket = ['semua' => 'Semua Barang', 'minimum' => 'Stok Menipis (<= stok minimum)', 'stok-habis' => 'Stok Habis', 'aman' => 'Stok Aman'][$selectedOption] ?? 'Semua Barang';
        $totalNilai = $barangs->sum(fn ($b) => (int) $b->stok * (int) $b->harga_beli);
    @endphp
    <h1>Laporan Stok</h1>
    <p>Keterangan : {{ $ket }} &middot; Total nilai persediaan: Rp {{ number_format($totalNilai, 0, ',', '.') }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jenis</th>
                <th>Lokasi</th>
                <th class="r">Stok</th>
                <th class="r">Min.</th>
                <th class="r">Harga Beli</th>
                <th class="r">Nilai</th>
                <th class="c">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($barangs as $index => $barang)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $barang->kode_barang }}</td>
                <td>{{ $barang->nama_barang }}</td>
                <td>{{ $barang->jenis->jenis_barang ?? '-' }}</td>
                <td>{{ $barang->lokasi ?: '-' }}</td>
                <td class="r">{{ (int) $barang->stok }} {{ $barang->satuan->satuan ?? '' }}</td>
                <td class="r">{{ (int) $barang->stok_minimum }}</td>
                <td class="r">{{ number_format((int) $barang->harga_beli, 0, ',', '.') }}</td>
                <td class="r">{{ number_format((int) $barang->stok * (int) $barang->harga_beli, 0, ',', '.') }}</td>
                <td class="c">{{ ucfirst($barang->status_stok) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak oleh: {{ auth()->user()->name }}<br>
        Tanggal: {{ date('d-m-Y') }}
    </div>
</body>
</html>
