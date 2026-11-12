<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\BarangMasuk;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BarangMasukController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('barang-masuk.index', [
            'barangs'      => Barang::all(),
            'barangsMasuk' => BarangMasuk::all(),
            'suppliers'    => Supplier::all()
        ]);
    }

    public function getDataBarangMasuk()
    {
        return response()->json([
            'success'   => true,
            'data'      => BarangMasuk::all(),
            'supplier'  => Supplier::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('barang-masuk.create', [
            'barangs'   => Barang::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_masuk'     => 'required|date',
            'nama_barang'       => 'required',
            'jumlah_masuk'      => 'required|integer|min:1',
            'supplier_id'       => 'required'
        ],[
            'tanggal_masuk.required'    => 'Pilih Tanggal Terlebih Dahulu !',
            'nama_barang.required'      => 'Pilih / Scan Barang Terlebih Dahulu !',
            'jumlah_masuk.required'     => 'Form Jumlah Stok Masuk Wajib Di Isi !',
            'jumlah_masuk.integer'      => 'Jumlah Harus Berupa Bilangan Bulat !',
            'jumlah_masuk.min'          => 'Jumlah Minimal 1 !',
            'supplier_id.required'      => 'Pilih Supplier !'
        ]);

        $validator->after(function ($v) use ($request) {
            if (!Barang::cari($request->kode_barang, $request->nama_barang)) {
                $v->errors()->add('nama_barang', 'Barang tidak ditemukan. Pilih barang atau scan barcode yang valid !');
            }
        });

        if($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $barangMasuk = DB::transaction(function () use ($request) {
            $barang = Barang::cari($request->kode_barang, $request->nama_barang);

            $barangMasuk = BarangMasuk::create([
                'tanggal_masuk'     => $request->tanggal_masuk,
                'kode_barang'       => $barang->kode_barang,
                'nama_barang'       => $barang->nama_barang,
                'jumlah_masuk'      => $request->jumlah_masuk,
                'supplier_id'       => $request->supplier_id,
                'kode_transaksi'    => $request->kode_transaksi ?: 'TRX-IN-' . now()->format('Ymd-His') . '-' . random_int(10, 99),
                'user_id'           => auth()->user()->id
            ]);

            $barang->increment('stok', (int) $request->jumlah_masuk);

            return $barangMasuk;
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Data Berhasil Disimpan !',
            'data'      => $barangMasuk
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(BarangMasuk $barangMasuk)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BarangMasuk $barangMasuk)
    {
        return response()->json([
            'success' => true,
            'message' => 'Edit Data Barang',
            'data'    => $barangMasuk
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BarangMasuk $barangMasuk)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BarangMasuk $barangMasuk)
    {
        DB::transaction(function () use ($barangMasuk) {
            $barang = Barang::cari($barangMasuk->kode_barang, $barangMasuk->nama_barang);
            if ($barang) {
                $barang->stok = max(0, (int) $barang->stok - (int) $barangMasuk->jumlah_masuk);
                $barang->save();
            }
            $barangMasuk->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Barang Berhasil Dihapus!'
        ]);
    }


    /**
     * Create Autocomplete Data
     */
    public function getAutoCompleteData(Request $request)
    {
        $barang = Barang::cari($request->kode_barang, $request->nama_barang);
        if($barang){
            return response()->json([
                'kode_barang'   => $barang->kode_barang,
                'nama_barang'   => $barang->nama_barang,
                'stok'          => $barang->stok,
                'satuan_id'     => $barang->satuan_id,
                'satuan'        => optional($barang->satuan)->satuan,
            ]);
        }

        return response()->json(['message' => 'Barang tidak terdaftar.'], 404);
    }

    /**
     * Get Satuan
     */
    public function getSatuan()
    {
       $satuans = Satuan::all();
       
       return response()->json($satuans);
    }

}
