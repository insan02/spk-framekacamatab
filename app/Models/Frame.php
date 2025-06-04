<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Frame extends Model
{
    use HasFactory;

    protected $table = 'frames';
    protected $primaryKey = 'frame_id';
    
    public $incrementing = false; // Jika frame_id bukan auto-increment
    protected $keyType = 'string'; // Jika frame_id adalah string
    
    protected $fillable = [
        'frame_id',
        'frame_merek',
        'frame_foto',
        'frame_lokasi'
    ];

    public function subkriterias()
    {
        return $this->belongsToMany(Subkriteria::class, 'frame_subkriteria', 'frame_id', 'subkriteria_id');
    }

    public function frameSubkriterias()
    {
        return $this->hasMany(FrameSubkriteria::class, 'frame_id');
    }
}