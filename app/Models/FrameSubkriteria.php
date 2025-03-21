<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrameSubkriteria extends Model
{
    use HasFactory;

    protected $table = 'frame_subkriteria';
    protected $primaryKey = 'framesubkriteria_id';
    
    protected $fillable = [
        'frame_id',
        'kriteria_id',
        'subkriteria_id',
    ];

    public function frame()
    {
        return $this->belongsTo(Frame::class, 'frame_id');
    }

    public function kriteria()
    {
        return $this->belongsTo(Kriteria::class, 'kriteria_id');
    }

    public function subkriteria()
    {
        return $this->belongsTo(Subkriteria::class, 'subkriteria_id');
    }
}