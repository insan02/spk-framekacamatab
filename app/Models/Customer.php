<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $primaryKey = 'customer_id';
    
    protected $fillable = [
        'name',
        'phone',
        'address'
    ];

    public function recommendationHistories()
    {
        return $this->hasMany(RecommendationHistory::class, 'customer_id');
    }
}