<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Satuan;
use App\Models\Customer;
use App\Models\BarangKeluar;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BarangKeluarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('barang-keluar.index', [
            'barangs'           => Barang::all(),
            'barangKeluar'      => BarangKeluar::all(),
            'customers'         => Customer::all()
        ]);
    }

    public function getDataBarangKeluar()
    {
        return response()->json([
            'success'   => true,
            'data'      => BarangKeluar::all(),
            'customer'  => Customer::all()
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('barang-keluar.create', [
            'barangs' => Barang::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_keluar'     => 'required|date',
            'nama_barang'        => 'required',
            'customer_id'        => 'required',
            'jumlah_keluar'      => 'required|integer|min:1',
        ],[
            'tanggal_keluar.required'    => 'Pilih Tanggal Terlebih Dahulu !',
            'nama_barang.required'       => 'Pilih / Scan Barang Terlebih Dahulu !',
            'jumlah_keluar.required'     => 'Form Jumlah Stok Keluar Wajib Di Isi !',
            'jumlah_keluar.integer'      => 'Jumlah Harus Berupa Bilangan Bulat !',
            'jumlah_keluar.min'          => 'Jumlah Minimal 1 !',
            'customer_id.required'       => 'Pilih Customer !'
        ]);

        $validator->after(function ($v) use ($request) {
            $barang = Barang::cari($request->kode_barang, $request->nama_barang);
            if (!$barang) {
                $v->errors()->add('nama_barang', 'Barang tidak ditemukan. Pilih barang atau scan barcode yang valid !');
            } elseif ((int) $request->jumlah_keluar > (int) $barang->stok) {
                $v->errors()->add('jumlah_keluar', 'Stok Tidak Cukup ! (tersedia ' . (int) $barang->stok . ')');
            }
        });

        if($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $barangKeluar = DB::transaction(function () use ($request) {
            // lockForUpdate: cegah dua transaksi bersamaan membuat stok minus
            $cari = Barang::cari($request->kode_barang, $request->nama_barang);
            $barang = Barang::where('id', $cari->id)->lockForUpdate()->first();

            if ((int) $request->jumlah_keluar > (int) $barang->stok) {
                abort(response()->json(['jumlah_keluar' => ['Stok Tidak Cukup ! (tersedia ' . (int) $barang->stok . ')']], 422));
            }

            $barangKeluar = BarangKeluar::create([
                'tanggal_keluar'    => $request->tanggal_keluar,
                'kode_barang'       => $barang->kode_barang,
                'nama_barang'       => $barang->nama_barang,
                'jumlah_keluar'     => $request->jumlah_keluar,
                'customer_id'       => $request->customer_id,
                'kode_transaksi'    => $request->kode_transaksi ?: 'TRX-OUT-' . now()->format('Ymd-His') . '-' . random_int(10, 99),
                'user_id'           => auth()->user()->id
            ]);

            $barang->decrement('stok', (int) $request->jumlah_keluar);

            return $barangKeluar;
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Data Berhasil Disimpan !',
            'data'      => $barangKeluar
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(BarangKeluar $barangKeluar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BarangKeluar $barangKeluar)
    {
        return response()->json([
            'success' => true,
            'message' => 'Edit Data Barang',
            'data'    => $barangKeluar
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BarangKeluar $barangKeluar)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BarangKeluar $barangKeluar)
    {
        DB::transaction(function () use ($barangKeluar) {
            $barang = Barang::cari($barangKeluar->kode_barang, $barangKeluar->nama_barang);
            if ($barang) {
                $barang->stok = (int) $barang->stok + (int) $barangKeluar->jumlah_keluar;
                $barang->save();
            }
            $barangKeluar->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Data Berhasil Dihapus!'
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
     * Create Autocomplete Data In Update Method
     */

    public function getStok(Request $request)
    {
        $namaBarang = $request->input('nama_barang');
        $barang = Barang::where('nama_barang', $namaBarang)->select('stok', 'satuan_id')->first();

        $response = [
            'stok'          => $barang->stok,
            'satuan_id'     => $barang->satuan_id
        ];

        return response()->json($response);
    }

    public function getSatuan()
    {
        $satuans = Satuan::all();
        
        return response()->json($satuans);
    }

    public function getBarangs(Request $request)
    {
        if ($request->has('q')) {
            $barangs = Barang::where('nama_barang', 'like', '%' . $request->input('q') . '%')->get();
            return response()->json($barangs);
        }

        return response()->json([]);
    }



}
