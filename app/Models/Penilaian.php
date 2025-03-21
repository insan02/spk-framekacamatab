<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    use HasFactory;

    protected $table = 'penilaians';
    protected $primaryKey = 'penilaian_id';
    
    protected $fillable = [
        'tgl_penilaian',
        'nama_pelanggan',
        'nohp_pelanggan',
        'alamat_pelanggan',
    ];

    protected $casts = [
        'tgl_penilaian' => 'date',
        'bobot_kriteria' => 'decimal:2',
    ];

    public function detailPenilaians()
    {
        return $this->hasMany(DetailPenilaian::class, 'penilaian_id');
    }

    public function rekomendasis()
    {
        return $this->hasMany(Rekomendasi::class, 'penilaian_id');
    }

    public function bobotKriterias()
    {
        return $this->hasMany(BobotKriteria::class, 'penilaian_id');
    }
}