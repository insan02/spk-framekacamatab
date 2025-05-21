<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kriteria extends Model
{
    use HasFactory;

    protected $table = 'kriterias';
    protected $primaryKey = 'kriteria_id';
    
    // Menambahkan properti untuk menandai bahwa primary key bukan auto-increment
    public $incrementing = false;
    
    // Karena kriteria_id adalah varchar, kita perlu menentukan tipenya
    protected $keyType = 'string';
    
    protected $fillable = [
        'kriteria_id', // Menambahkan kriteria_id ke fillable
        'kriteria_nama',
        'bobot_kriteria',
    ];

    protected $casts = [
        'bobot_kriteria' => 'decimal:2',
    ];

    public function subkriterias()
    {
        return $this->hasMany(Subkriteria::class, 'kriteria_id');
    }

    public function frameSubkriterias()
    {
        return $this->hasMany(FrameSubkriteria::class, 'kriteria_id');
    }
    
    // Method untuk generate ID baru dengan format C diikuti angka
    public static function generateNewId()
    {
        $lastKriteria = self::orderBy('kriteria_id', 'desc')->first();
        
        if (!$lastKriteria) {
            return 'C01'; // ID pertama jika belum ada data
        }
        
        // Ambil angka dari ID terakhir
        $lastNumber = (int) substr($lastKriteria->kriteria_id, 1);
        $newNumber = $lastNumber + 1;
        
        // Format angka dengan leading zero
        return 'C' . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
    }
}