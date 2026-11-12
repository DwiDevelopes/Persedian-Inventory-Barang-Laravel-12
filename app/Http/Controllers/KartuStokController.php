<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\PenyesuaianStok;
use Illuminate\Http\Request;

/**
 * Kartu stok: mutasi (masuk, keluar, penyesuaian) satu barang beserta saldo berjalan.
 */
class KartuStokController extends Controller
{
    public function index()
    {
        return view('kartu-stok.index', ['barangs' => Barang::orderBy('nama_barang')->get(['id', 'kode_barang', 'nama_barang'])]);
    }

    public function getData(Request $request)
    {
        $barang = Barang::with('satuan')->where('kode_barang', $request->kode)->first();
        if (!$barang) {
            return response()->json(['success' => false, 'message' => 'Barang tidak ditemukan'], 404);
        }

        $dari   = $request->filled('dari') ? $request->dari : null;
        $sampai = $request->filled('sampai') ? $request->sampai : null;

        $mutasi = collect();
        foreach (BarangMasuk::where('kode_barang', $barang->kode_barang)->get() as $t) {
            $mutasi->push(['tanggal' => substr((string) $t->tanggal_masuk, 0, 10), 'at' => $t->created_at, 'kode' => $t->kode_transaksi, 'jenis' => 'masuk', 'qty' => (int) $t->jumlah_masuk, 'ket' => optional($t->supplier)->supplier]);
        }
        foreach (BarangKeluar::with('customer')->where('kode_barang', $barang->kode_barang)->get() as $t) {
            $mutasi->push(['tanggal' => substr((string) $t->tanggal_keluar, 0, 10), 'at' => $t->created_at, 'kode' => $t->kode_transaksi, 'jenis' => 'keluar', 'qty' => -(int) $t->jumlah_keluar, 'ket' => optional($t->customer)->customer]);
        }
        foreach (PenyesuaianStok::where('kode_barang', $barang->kode_barang)->get() as $t) {
            $mutasi->push(['tanggal' => substr((string) $t->tanggal, 0, 10), 'at' => $t->created_at, 'kode' => $t->kode_transaksi, 'jenis' => 'penyesuaian', 'qty' => (int) $t->selisih, 'ket' => $t->keterangan]);
        }
        $mutasi = $mutasi->sortBy([['tanggal', 'asc'], ['at', 'asc']])->values();

        // Stok awal (saat barang didaftarkan) = stok sekarang dikurangi seluruh mutasi
        $awal  = (int) $barang->stok - (int) $mutasi->sum('qty');
        $saldo = $awal;

        $baris = [];
        $saldoSebelum = $awal;
        foreach ($mutasi as $m) {
            if ($dari && $m['tanggal'] < $dari) {
                $saldo += $m['qty'];
                $saldoSebelum = $saldo;
                continue;
            }
            if ($sampai && $m['tanggal'] > $sampai) {
                continue;
            }
            $saldo += $m['qty'];
            $baris[] = [
                'tanggal' => $m['tanggal'],
                'kode'    => $m['kode'],
                'jenis'   => $m['jenis'],
                'masuk'   => $m['qty'] > 0 ? $m['qty'] : 0,
                'keluar'  => $m['qty'] < 0 ? -$m['qty'] : 0,
                'saldo'   => $saldo,
                'ket'     => $m['ket'],
            ];
        }

        return response()->json([
            'success' => true,
            'barang'  => [
                'kode_barang' => $barang->kode_barang,
                'nama_barang' => $barang->nama_barang,
                'satuan'      => $barang->satuan->satuan ?? '',
                'stok'        => (int) $barang->stok,
                'stok_minimum'=> (int) $barang->stok_minimum,
            ],
            'saldo_awal' => $dari ? $saldoSebelum : $awal,
            'rows'       => $baris,
            'total_masuk'  => collect($baris)->sum('masuk'),
            'total_keluar' => collect($baris)->sum('keluar'),
        ]);
    }
}
