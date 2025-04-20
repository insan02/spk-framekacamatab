<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationHistory extends Model
{
    use HasFactory;

    protected $primaryKey = 'recommendation_history_id';

    protected $fillable = [
        'customer_id', // Keep this for reference
        'customer_name', // Add customer snapshot data
        'customer_phone',
        'customer_address',
        'kriteria_dipilih',
        'bobot_kriteria',
        'rekomendasi_data',
        'perhitungan_detail'
    ];

    // Relationship with Customer - can be nullable now
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault([
            'name' => $this->customer_name,
            'phone' => $this->customer_phone,
            'address' => $this->customer_address
        ]);
    }

    // JSON mutators and accessors remain the same
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

    // Accessor methods
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