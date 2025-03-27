<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationHistory extends Model
{
    use HasFactory;

    protected $primaryKey = 'recommendation_history_id';

    protected $fillable = [
        'nama_pelanggan',
        'nohp_pelanggan',
        'alamat_pelanggan',
        'kriteria_dipilih',
        'bobot_kriteria',
        'rekomendasi_data',
        'perhitungan_detail'
    ];

    // Mutator to ensure JSON storage for complex data
    public function setKriteriaDipilihAttribute($value)
    {
        $this->attributes['kriteria_dipilih'] = is_array($value) 
            ? json_encode($value) 
            : $value;
    }

    public function setBobotKriteriaAttribute($value)
    {
        $this->attributes['bobot_kriteria'] = is_array($value) 
            ? json_encode($value) 
            : $value;
    }

    public function setRekomendasiDataAttribute($value)
    {
        $this->attributes['rekomendasi_data'] = is_array($value) 
            ? json_encode($value) 
            : $value;
    }

    public function setPerhitunganDetailAttribute($value)
    {
        $this->attributes['perhitungan_detail'] = is_array($value) 
            ? json_encode($value) 
            : $value;
    }

    // Accessor methods to parse JSON
    public function getKriteriaDipilihAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getBobotKriteriaAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getRekomendasiDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function getPerhitunganDetailAttribute($value)
    {
        return json_decode($value, true);
    }
}