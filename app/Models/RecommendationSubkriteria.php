<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationSubkriteria extends Model
{
    use HasFactory;

    protected $table = 'recommendation_subkriteria';
    protected $primaryKey = 'rec_subkriteria_id';
    
    protected $fillable = [
        'recommendation_history_id',
        'subkriteria_id',
        'kriteria_id',
        'subkriteria_nama',
        'subkriteria_bobot',
    ];
    
    protected $casts = [
        'subkriteria_bobot' => 'float',
    ];
    
    public function recommendationHistory()
    {
        return $this->belongsTo(RecommendationHistory::class, 'recommendation_history_id');
    }
    
    public function subkriteria()
    {
        return $this->belongsTo(Subkriteria::class, 'subkriteria_id');
    }
    
    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria_id');
    }
}
