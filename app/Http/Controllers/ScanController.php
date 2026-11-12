<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;

/**
 * Halaman Scan Barcode: scan -> info barang + riwayat -> catat masuk / keluar cepat.
 */
class ScanController extends Controller
{
    public function index()
    {
        return view('scan.index', [
            'suppliers' => Supplier::all(),
            'customers' => Customer::all(),
        ]);
    }

    public function lookup(Request $request)
    {
        $barang = Barang::with(['satuan', 'jenis'])->where('kode_barang', trim((string) $request->kode))->first();

        if (!$barang) {
            return response()->json(['success' => false, 'message' => 'Kode "' . e($request->kode) . '" tidak terdaftar.'], 404);
        }

        $riwayat = BarangMasuk::where('kode_barang', $barang->kode_barang)->latest()->limit(5)->get()->map(fn ($t) => [
            'jenis' => 'masuk', 'kode' => $t->kode_transaksi, 'tanggal' => $t->tanggal_masuk, 'jumlah' => $t->jumlah_masuk, 'at' => $t->created_at,
        ])->concat(BarangKeluar::where('kode_barang', $barang->kode_barang)->latest()->limit(5)->get()->map(fn ($t) => [
            'jenis' => 'keluar', 'kode' => $t->kode_transaksi, 'tanggal' => $t->tanggal_keluar, 'jumlah' => $t->jumlah_keluar, 'at' => $t->created_at,
        ]))->sortByDesc('at')->take(6)->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $barang->id,
                'kode_barang'  => $barang->kode_barang,
                'nama_barang'  => $barang->nama_barang,
                'gambar'       => $barang->gambar,
                'jenis'        => $barang->jenis->jenis_barang ?? '-',
                'satuan'       => $barang->satuan->satuan ?? '',
                'stok'         => (int) $barang->stok,
                'stok_minimum' => (int) $barang->stok_minimum,
                'status'       => $barang->status_stok,
                'lokasi'       => $barang->lokasi,
                'harga_beli'   => (int) $barang->harga_beli,
                'harga_jual'   => (int) $barang->harga_jual,
            ],
            'riwayat' => $riwayat,
        ]);
    }
}
