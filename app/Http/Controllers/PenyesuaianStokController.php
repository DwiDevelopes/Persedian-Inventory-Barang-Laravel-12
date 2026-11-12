<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PenyesuaianStok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Stok opname / penyesuaian stok: samakan stok sistem dengan hasil hitung fisik.
 */
class PenyesuaianStokController extends Controller
{
    public function index()
    {
        return view('penyesuaian-stok.index', ['barangs' => Barang::orderBy('nama_barang')->get()]);
    }

    public function getData()
    {
        return response()->json(['success' => true, 'data' => PenyesuaianStok::with('user:id,name')->latest()->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'     => 'required|date',
            'kode_barang' => 'required',
            'stok_fisik'  => 'required|integer|min:0',
            'keterangan'  => 'nullable|string|max:255',
        ], [
            'tanggal.required'     => 'Pilih tanggal !',
            'kode_barang.required' => 'Pilih / scan barang terlebih dahulu !',
            'stok_fisik.required'  => 'Isi stok hasil hitung fisik !',
            'stok_fisik.integer'   => 'Stok fisik harus bilangan bulat !',
            'stok_fisik.min'       => 'Stok fisik tidak boleh kurang dari 0 !',
        ]);

        $validator->after(function ($v) use ($request) {
            if ($request->filled('kode_barang') && !Barang::where('kode_barang', $request->kode_barang)->exists()) {
                $v->errors()->add('kode_barang', 'Barang tidak ditemukan !');
            }
        });

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $row = DB::transaction(function () use ($request) {
            $barang = Barang::where('kode_barang', $request->kode_barang)->lockForUpdate()->first();
            $sistem = (int) $barang->stok;
            $fisik  = (int) $request->stok_fisik;

            $row = PenyesuaianStok::create([
                'kode_transaksi' => 'ADJ-' . now()->format('Ymd-His') . '-' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT),
                'tanggal'        => $request->tanggal,
                'kode_barang'    => $barang->kode_barang,
                'nama_barang'    => $barang->nama_barang,
                'stok_sistem'    => $sistem,
                'stok_fisik'     => $fisik,
                'selisih'        => $fisik - $sistem,
                'keterangan'     => $request->keterangan,
                'user_id'        => auth()->id(),
            ]);

            $barang->stok = $fisik;
            $barang->save();

            return $row;
        });

        return response()->json(['success' => true, 'message' => 'Stok berhasil disesuaikan !', 'data' => $row]);
    }
}
