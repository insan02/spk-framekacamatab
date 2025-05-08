<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RecommendationHistory extends Model
{
    use HasFactory;

    protected $primaryKey = 'recommendation_history_id';

    protected $fillable = [
        'customer_id',
        'user_id', // Added user_id to fillable
        'customer_name',
        'customer_phone',
        'customer_address',
        'kriteria_dipilih',
        'bobot_kriteria',
        'rekomendasi_data',
        'perhitungan_detail'
    ];

    protected static function booted()
    {
        static::deleting(function ($recommendationHistory) {
            // Extract image paths from rekomendasi_data
            $rekomendasiData = $recommendationHistory->rekomendasi_data;
            if (is_array($rekomendasiData)) {
                foreach ($rekomendasiData as $item) {
                    if (isset($item['frame']['frame_foto'])) {
                        $imagePath = $item['frame']['frame_foto'];
                        // Check if it's a history image (safeguard to not delete original images)
                        if (strpos($imagePath, 'history_images/') === 0) {
                            // Delete the image file
                            Storage::disk('public')->delete($imagePath);
                        }
                    }
                }
            }
        });
    }

    // Relationship with Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault([
            'name' => $this->customer_name,
            'phone' => $this->customer_phone,
            'address' => $this->customer_address
        ]);
    }
    
    // Adding relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Adding relationships with recommendation junction tables
    public function recommendationCriteria()
    {
        return $this->hasMany(RecommendationCriteria::class, 'recommendation_history_id');
    }
    
    public function recommendationSubkriteria()
    {
        return $this->hasMany(RecommendationSubkriteria::class, 'recommendation_history_id');
    }
    
    public function recommendationFrames()
    {
        return $this->hasMany(RecommendationFrame::class, 'recommendation_history_id');
    }

    // JSON mutators and accessors
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