<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subkriteria extends Model
{
    use HasFactory;

    protected $table = 'subkriterias';
    protected $primaryKey = 'subkriteria_id';
    
    protected $fillable = [
        'kriteria_id',
        'subkriteria_nama',
        'subkriteria_bobot',
        'tipe_subkriteria', // Pastikan field ini ada
    'operator',
    'nilai_minimum',
    'nilai_maksimum'
    ];

    protected $casts = [
        'subkriteria_bobot' => 'float',
    ];

    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria_id');
    }

    public function frameSubkriterias()
    {
        return $this->hasMany(FrameSubkriteria::class, 'subkriteria_id');
    }

}