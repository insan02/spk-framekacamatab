<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    use HasFactory;

    protected $table = 'frames';
    protected $primaryKey = 'frame_id';
    
    protected $fillable = [
        'frame_merek',
        'frame_foto',
        'frame_harga',
    ];

    public function frameSubkriterias()
    {
        return $this->hasMany(FrameSubkriteria::class, 'frame_id');
    }

    public function rekomendasis()
    {
        return $this->hasMany(Rekomendasi::class, 'frame_id');
    }
}