<?php

namespace App\Models;

use App\Models\User;
use App\Models\Jenis;
use App\Models\Satuan;
use App\Models\BarangMasuk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Barang extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = ['kode_barang', 'nama_barang', 'deskripsi', 'gambar', 'stok_minimum', 'jenis_id', 'stok', 'satuan_id', 'user_id', 'lokasi', 'harga_beli', 'harga_jual'];
    protected $guarded = [''];
    protected $ignoreChangedAttributes = ['updated_at'];


    public function getActivitylogAttributes(): array
    {
        return array_diff($this->fillable, $this->ignoreChangedAttributes);
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()
            ->logOnlyDirty();
    }

    // Satu Barang dimiliki oleh 1 User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Satu Barang memiliki 1 jenis
    public function jenis()
    {
        return $this->belongsTo(Jenis::class, 'jenis_id');
    }

    // Satu Barang memiliki 1 satuan
    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    /**
     * Status stok berdasarkan stok minimum milik barang itu sendiri.
     * habis | menipis | aman
     */
    public function getStatusStokAttribute(): string
    {
        $stok = (int) $this->stok;
        if ($stok <= 0) {
            return 'habis';
        }
        return $stok <= (int) $this->stok_minimum ? 'menipis' : 'aman';
    }

    public function getNilaiPersediaanAttribute(): int
    {
        return (int) $this->stok * (int) $this->harga_beli;
    }

    /**
     * Cari barang dari kode barang (hasil scan barcode) atau nama barang.
     */
    public static function cari(?string $kode = null, ?string $nama = null): ?self
    {
        if ($kode !== null && $kode !== '') {
            $b = static::where('kode_barang', trim($kode))->first();
            if ($b) {
                return $b;
            }
        }
        if ($nama !== null && $nama !== '') {
            return static::where('nama_barang', $nama)->first();
        }
        return null;
    }

    /**
     * Buat kode barang berurutan: BRG-00001, BRG-00002, ...
     */
    public static function buatKode(): string
    {
        $max = static::where('kode_barang', 'like', 'BRG-%')->pluck('kode_barang')
            ->map(fn ($k) => (int) substr($k, 4))->max() ?? 0;
        do {
            $kode = 'BRG-' . str_pad(++$max, 5, '0', STR_PAD_LEFT);
        } while (static::where('kode_barang', $kode)->exists());

        return $kode;
    }
}
