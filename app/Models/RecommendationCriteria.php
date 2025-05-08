<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationCriteria extends Model
{
    use HasFactory;

    protected $table = 'recommendation_criteria';
    protected $primaryKey = 'rec_criteria_id';
    
    protected $fillable = [
        'recommendation_history_id',
        'kriteria_id',
        'kriteria_nama',
        'kriteria_bobot',
    ];
    
    protected $casts = [
        'kriteria_bobot' => 'float',
    ];
    
    public function recommendationHistory()
    {
        return $this->belongsTo(RecommendationHistory::class, 'recommendation_history_id');
    }
    
    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria_id');
    }
}