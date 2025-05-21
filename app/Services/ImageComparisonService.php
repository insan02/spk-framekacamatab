<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use App\Models\Frame;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ImageComparisonService
{
    // Turunkan threshold untuk lebih toleran
    const SIMILARITY_THRESHOLD = 25;
    
    // Kecilkan dimensi untuk memperoleh performa lebih baik
    const COMPARE_WIDTH = 32;
    const COMPARE_HEIGHT = 32;
    
    // Definisikan berapa lama signature akan di-cache (dalam menit)
    const CACHE_DURATION = 1440; // 24 jam
    
    // Jumlah pixel yang akan disampling (tidak menggunakan semua pixel)
    const SAMPLE_POINTS = 256; // Jumlah pixel yang akan diambil sebagai sampel
    
    /**
     * Mencari kesamaan gambar dengan gambar frame yang sudah ada
     * 
     * @param UploadedFile $uploadedImage
     * @param bool $returnAllMatches Optional parameter to return all matches (default: true)
     * @return array|null Array of similar frames, or null if no matches found
     */
    public function findSimilarFrame(UploadedFile $uploadedImage, bool $returnAllMatches = true)
    {
        try {
            // Buat signature dari gambar yang diunggah
            $uploadedSignature = $this->createImageSignature($uploadedImage);
            
            // Ambil semua frame dari database - OPTIMASI: Batasi kolom yang diambil
            $frames = Frame::select(['frame_id', 'frame_foto'])->get();
            
            $matches = [];
            $matchScores = [];
            
            // OPTIMASI: Hanya proses 50 frame terlebih dahulu dengan perghitungan cepat 
            // untuk mendapatkan kandidat yang potensial
            $potentialMatches = $this->getQuickPotentialMatches($uploadedSignature, $frames);
            
            // Hanya lakukan perbandingan detail pada kandidat potensial
            foreach ($potentialMatches as $frame) {
                // Jika frame tidak memiliki foto, lewati
                if (!$frame->frame_foto || !Storage::disk('public')->exists($frame->frame_foto)) {
                    continue;
                }
                
                // Buat path lengkap ke file gambar
                $framePath = storage_path('app/public/' . $frame->frame_foto);
                
                // Cek apakah signature sudah di-cache
                $cacheKey = 'frame_signature_' . md5($frame->frame_foto);
                $frameSignature = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($framePath) {
                    return $this->createImageSignatureFromPath($framePath);
                });
                
                // Bandingkan signatures
                $difference = $this->compareSignatures($uploadedSignature, $frameSignature);
                
                // Simpan score untuk sorting
                $matchScores[$frame->frame_id] = $difference;
                
                // Jika perbedaan di bawah threshold, tambahkan ke matches
                if ($difference < self::SIMILARITY_THRESHOLD) {
                    // OPTIMASI: Load data frame lengkap hanya jika ada match
                    $matches[$frame->frame_id] = Frame::find($frame->frame_id);
                }
            }
            
            // Tidak ada kecocokan yang ditemukan
            if (empty($matches)) {
                return null;
            }
            
            // Sort matches berdasarkan similarity (terendah = paling mirip)
            asort($matchScores);
            
            // Ambil semua atau hanya yang teratas
            if (!$returnAllMatches) {
                // Ambil hanya match teratas
                $topFrameId = array_keys($matchScores)[0];
                return [$matches[$topFrameId]];
            }
            
            // Return all matches sorted by similarity
            $result = [];
            foreach (array_keys($matchScores) as $frameId) {
                if (isset($matches[$frameId])) {
                    $result[] = $matches[$frameId];
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error in image comparison: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Metode untuk cepat menemukan kandidat potensial 
     * menggunakan perhitungan sederhana
     */
    protected function getQuickPotentialMatches($uploadedSignature, $frames, $maxCandidates = 50)
    {
        $candidates = [];
        $scores = [];
        
        foreach ($frames as $frame) {
            // Lewati jika tidak ada foto
            if (!$frame->frame_foto || !Storage::disk('public')->exists($frame->frame_foto)) {
                continue;
            }
            
            $framePath = storage_path('app/public/' . $frame->frame_foto);
            
            // Cek apakah signature sudah di-cache
            $cacheKey = 'frame_signature_quick_' . md5($frame->frame_foto);
            $frameSignature = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($framePath) {
                // Gunakan perbandingan cepat dengan ukuran sangat kecil
                return $this->createQuickSignature($framePath);
            });
            
            // Bandingkan dengan cepat
            $quickDiff = $this->quickCompare($uploadedSignature, $frameSignature);
            
            $scores[$frame->frame_id] = $quickDiff;
            $candidates[$frame->frame_id] = $frame;
        }
        
        // Urutkan kandidat berdasarkan skor
        asort($scores);
        
        // Ambil top kandidat
        $result = [];
        $count = 0;
        foreach (array_keys($scores) as $frameId) {
            if (isset($candidates[$frameId]) && $count < $maxCandidates) {
                $result[] = $candidates[$frameId];
                $count++;
            }
        }
        
        return $result;
    }
    
    /**
     * Signature cepat dengan ukuran sangat kecil
     */
    protected function createQuickSignature($path)
    {
        $image = Image::make($path);
        
        // Resize menjadi sangat kecil
        $image->resize(8, 8);
        $image->greyscale();
        
        $signature = [];
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $color = $image->pickColor($x, $y);
                $signature[] = $color[0];
            }
        }
        
        return $signature;
    }
    
    /**
     * Bandingkan dua signature dengan cepat
     */
    protected function quickCompare($sig1, $sig2)
    {
        $diff = 0;
        $len = min(count($sig1), count($sig2));
        
        for ($i = 0; $i < $len; $i++) {
            $diff += abs($sig1[$i] - $sig2[$i]);
        }
        
        return $diff / $len;
    }
    
    /**
     * Buat signature dari file gambar yang diupload
     * 
     * @param UploadedFile $file
     * @return array
     */
    public function createImageSignature(UploadedFile $file)
    {
        // Gunakan Intervention Image untuk memanipulasi gambar
        $image = Image::make($file);
        
        return $this->processImageToSignature($image);
    }
    
    /**
     * Buat signature dari file gambar berdasarkan path
     * 
     * @param string $path
     * @return array
     */
    public function createImageSignatureFromPath(string $path)
    {
        // Gunakan Intervention Image untuk memanipulasi gambar
        $image = Image::make($path);
        
        return $this->processImageToSignature($image);
    }
    
    /**
     * Proses gambar dan buat signature
     * 
     * @param \Intervention\Image\Image $image
     * @return array
     */
    protected function processImageToSignature($image)
    {
        // OPTIMASI: Resize gambar ke ukuran yang jauh lebih kecil
        $image->resize(self::COMPARE_WIDTH, self::COMPARE_HEIGHT);
        
        // Konversi ke grayscale
        $image->greyscale();
        
        // Kontras untuk mempertajam fitur
        $image->contrast(15);
        
        // OPTIMASI: Gunakan hashing teknik perceptual hashing sederhana
        // dengan mengambil sampel pixel daripada menggunakan semua pixel
        
        $width = self::COMPARE_WIDTH;
        $height = self::COMPARE_HEIGHT;
        
        // Tentukan jumlah sampel yang akan diambil
        $sampleStepX = max(1, floor($width / sqrt(self::SAMPLE_POINTS)));
        $sampleStepY = max(1, floor($height / sqrt(self::SAMPLE_POINTS)));
        
        $signature = [];
        
        // Ambil sampel pixel saja, bukan semua pixel
        for ($y = 0; $y < $height; $y += $sampleStepY) {
            for ($x = 0; $x < $width; $x += $sampleStepX) {
                // Ambil warna dari pixel
                $color = $image->pickColor($x, $y);
                $signature[] = $color[0];
            }
        }
        
        return $signature;
    }
    
    /**
     * Bandingkan dua signature gambar dan hitung perbedaannya
     * OPTIMASI: Penggunaan algoritma perbandingan yang lebih efisien
     * 
     * @param array $signature1
     * @param array $signature2
     * @return float Nilai perbedaan (0 = identik, semakin besar = semakin berbeda)
     */
    protected function compareSignatures(array $signature1, array $signature2)
    {
        // Pastikan kedua signature memiliki ukuran yang sama
        $len1 = count($signature1);
        $len2 = count($signature2);
        
        if ($len1 !== $len2) {
            // Jika ukuran tidak sama, ambil ukuran yang lebih kecil
            $minLen = min($len1, $len2);
            $signature1 = array_slice($signature1, 0, $minLen);
            $signature2 = array_slice($signature2, 0, $minLen);
        }
        
        // OPTIMASI: Gunakan Mean Absolute Error (MAE) yang lebih cepat dihitung
        $pixelCount = count($signature1);
        $totalDiff = 0;
        
        for ($i = 0; $i < $pixelCount; $i++) {
            $totalDiff += abs($signature1[$i] - $signature2[$i]);
        }
        
        // Normalisasi perbedaan (0-100)
        return ($totalDiff / $pixelCount) * 100 / 255;
    }
}