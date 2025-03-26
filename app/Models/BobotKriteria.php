<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BobotKriteria extends Model
{
    use HasFactory;

    protected $table = 'bobot_kriterias';
    protected $primaryKey = 'bobotkriteria_id';

    protected $fillable = [
        'penilaian_id',
        'kriteria_id',
        'nilai_bobot',
        'kriteria_nama_snapshot'
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Penilaian::class, 'penilaian_id', 'penilaian_id');
    }

    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria_id', 'kriteria_id');
    }
}
