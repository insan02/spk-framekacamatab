<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationFrame extends Model
{
    use HasFactory;

    protected $table = 'recommendation_frames';
    protected $primaryKey = 'rec_frame_id';
    
    protected $fillable = [
        'recommendation_history_id',
        'frame_id',
        'frame_nama',
        'skor_akhir',
        'peringkat',
    ];
    
    protected $casts = [
        'skor_akhir' => 'decimal:2',
        'peringkat' => 'integer',
    ];
    
    public function recommendationHistory()
    {
        return $this->belongsTo(RecommendationHistory::class, 'recommendation_history_id');
    }
    
    public function frame()
    {
        return $this->belongsTo(Frame::class, 'frame_id');
    }
}
