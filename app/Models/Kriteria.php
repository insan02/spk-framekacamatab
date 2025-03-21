<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kriteria extends Model
{
    use HasFactory;

    protected $table = 'kriterias';
    protected $primaryKey = 'kriteria_id';
    
    protected $fillable = [
        'kriteria_nama',
        'bobot_kriteria',
    ];

    protected $casts = [
        'bobot_kriteria' => 'decimal:2',
    ];

    public function subkriterias()
    {
        return $this->hasMany(Subkriteria::class, 'kriteria_id');
    }

    public function frameSubkriterias()
    {
        return $this->hasMany(FrameSubkriteria::class, 'kriteria_id');
    }

    public function detailPenilaians()
    {
        return $this->hasMany(DetailPenilaian::class, 'kriteria_id');
    }
    
    public function bobotKriterias()
    {
        return $this->hasMany(BobotKriteria::class, 'kriteria_id');
    }
}