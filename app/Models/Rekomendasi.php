<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekomendasi extends Model
{
    use HasFactory;

    protected $table = 'rekomendasis';
    protected $primaryKey = 'rekomendasi_id';
    
    protected $fillable = [
        'frame_id',
        'penilaian_id',
        'nilai_akhir',
        'rangking',
    ];

    protected $casts = [
        'nilai_akhir' => 'float',
        'rangking' => 'integer',
    ];

    public function frame()
    {
        return $this->belongsTo(Frame::class, 'frame_id');
    }

    public function penilaian()
    {
        return $this->belongsTo(Penilaian::class, 'penilaian_id');
    }
}