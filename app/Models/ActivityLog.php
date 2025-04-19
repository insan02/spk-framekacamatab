<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module',
        'reference_id',
        'old_values',
        'new_values',
        'description'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}