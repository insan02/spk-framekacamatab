<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use App\Models\Frame;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

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
                if (!$frame->frame_foto) {
                    continue;
                }
                
                // Gunakan StorageService untuk cek keberadaan file
                $fileCheck = StorageService::checkFileExistence($frame->frame_foto);
                
                if (!$fileCheck['storage_exists'] && !$fileCheck['public_exists']) {
                    Log::warning("Frame image not found", [
                        'frame_id' => $frame->frame_id,
                        'frame_foto' => $frame->frame_foto
                    ]);
                    continue;
                }
                
                // Prioritas: gunakan file dari storage, fallback ke public
                $framePath = $fileCheck['storage_exists'] ? 
                    $fileCheck['storage_path'] : 
                    $fileCheck['public_path'];
                
                // Cek apakah signature sudah di-cache
                $cacheKey = 'frame_signature_' . md5($frame->frame_foto . filemtime($framePath));
                $frameSignature = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($framePath) {
                    return $this->createImageSignatureFromPath($framePath);
                });
                
                if ($frameSignature === null) {
                    Log::warning("Failed to create signature for frame", [
                        'frame_id' => $frame->frame_id,
                        'path' => $framePath
                    ]);
                    continue;
                }
                
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
                Log::info("No similar frames found", [
                    'uploaded_signature_length' => count($uploadedSignature),
                    'frames_checked' => count($potentialMatches)
                ]);
                return null;
            }
            
            // Sort matches berdasarkan similarity (terendah = paling mirip)
            asort($matchScores);
            
            // Log hasil untuk debugging
            Log::info("Similar frames found", [
                'total_matches' => count($matches),
                'best_score' => array_values($matchScores)[0],
                'threshold' => self::SIMILARITY_THRESHOLD
            ]);
            
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
            Log::error('Error in image comparison: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
            if (!$frame->frame_foto) {
                continue;
            }
            
            // Gunakan StorageService untuk cek keberadaan file
            $fileCheck = StorageService::checkFileExistence($frame->frame_foto);
            
            if (!$fileCheck['storage_exists'] && !$fileCheck['public_exists']) {
                continue;
            }
            
            // Prioritas: gunakan file dari storage, fallback ke public
            $framePath = $fileCheck['storage_exists'] ? 
                $fileCheck['storage_path'] : 
                $fileCheck['public_path'];
            
            // Cek apakah signature sudah di-cache
            $cacheKey = 'frame_signature_quick_' . md5($frame->frame_foto . filemtime($framePath));
            $frameSignature = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($framePath) {
                // Gunakan perbandingan cepat dengan ukuran sangat kecil
                return $this->createQuickSignature($framePath);
            });
            
            if ($frameSignature === null) {
                continue;
            }
            
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
        
        Log::info("Quick potential matches found", [
            'total_candidates' => count($result),
            'max_candidates' => $maxCandidates
        ]);
        
        return $result;
    }
    
    /**
     * Signature cepat dengan ukuran sangat kecil
     */
    protected function createQuickSignature($path)
    {
        try {
            if (!File::exists($path)) {
                Log::error("File not found for quick signature", ['path' => $path]);
                return null;
            }
            
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
        } catch (\Exception $e) {
            Log::error('Error creating quick signature: ' . $e->getMessage(), [
                'path' => $path
            ]);
            return null;
        }
    }
    
    /**
     * Bandingkan dua signature dengan cepat
     */
    protected function quickCompare($sig1, $sig2)
    {
        if (empty($sig1) || empty($sig2)) {
            return PHP_INT_MAX; // Return maksimum jika salah satu signature kosong
        }
        
        $diff = 0;
        $len = min(count($sig1), count($sig2));
        
        for ($i = 0; $i < $len; $i++) {
            $diff += abs($sig1[$i] - $sig2[$i]);
        }
        
        return $len > 0 ? $diff / $len : PHP_INT_MAX;
    }
    
    /**
     * Buat signature dari file gambar yang diupload
     * 
     * @param UploadedFile $file
     * @return array
     */
    public function createImageSignature(UploadedFile $file)
    {
        try {
            // Gunakan Intervention Image untuk memanipulasi gambar
            $image = Image::make($file);
            
            return $this->processImageToSignature($image);
        } catch (\Exception $e) {
            Log::error('Error creating image signature from upload: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buat signature dari file gambar berdasarkan path
     * 
     * @param string $path
     * @return array|null
     */
    public function createImageSignatureFromPath(string $path)
    {
        try {
            if (!File::exists($path)) {
                Log::error("File not found for signature creation", ['path' => $path]);
                return null;
            }
            
            // Gunakan Intervention Image untuk memanipulasi gambar
            $image = Image::make($path);
            
            return $this->processImageToSignature($image);
        } catch (\Exception $e) {
            Log::error('Error creating image signature from path: ' . $e->getMessage(), [
                'path' => $path
            ]);
            return null;
        }
    }
    
    /**
     * Proses gambar dan buat signature
     * 
     * @param \Intervention\Image\Image $image
     * @return array
     */
    protected function processImageToSignature($image)
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error processing image to signature: ' . $e->getMessage());
            return [];
        }
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
        // Cek apakah kedua signature tidak kosong
        if (empty($signature1) || empty($signature2)) {
            return PHP_INT_MAX; // Return nilai maksimum jika salah satu signature kosong
        }
        
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
        
        if ($pixelCount === 0) {
            return PHP_INT_MAX;
        }
        
        $totalDiff = 0;
        
        for ($i = 0; $i < $pixelCount; $i++) {
            $totalDiff += abs($signature1[$i] - $signature2[$i]);
        }
        
        // Normalisasi perbedaan (0-100)
        return ($totalDiff / $pixelCount) * 100 / 255;
    }
    
    /**
     * Sinkronisasi gambar frame yang belum tersedia di public storage
     * Berguna untuk memastikan semua gambar frame dapat diakses
     * 
     * @return array
     */
    public function syncFrameImages()
    {
        try {
            $frames = Frame::whereNotNull('frame_foto')->get();
            $results = [
                'total' => count($frames),
                'synced' => 0,
                'failed' => 0,
                'already_synced' => 0
            ];
            
            foreach ($frames as $frame) {
                $fileCheck = StorageService::checkFileExistence($frame->frame_foto);
                
                if ($fileCheck['storage_exists'] && !$fileCheck['public_exists']) {
                    // File ada di storage tapi tidak di public
                    if (StorageService::syncToPublic($frame->frame_foto)) {
                        $results['synced']++;
                    } else {
                        $results['failed']++;
                    }
                } elseif ($fileCheck['synchronized']) {
                    $results['already_synced']++;
                } else {
                    $results['failed']++;
                }
            }
            
            Log::info("Frame images sync completed", $results);
            return $results;
        } catch (\Exception $e) {
            Log::error('Error syncing frame images: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Membersihkan cache signature untuk frame tertentu
     * 
     * @param string $framePhoto
     * @return bool
     */
    public function clearFrameSignatureCache(string $framePhoto)
    {
        try {
            $fileCheck = StorageService::checkFileExistence($framePhoto);
            
            if ($fileCheck['storage_exists'] || $fileCheck['public_exists']) {
                $filePath = $fileCheck['storage_exists'] ? 
                    $fileCheck['storage_path'] : 
                    $fileCheck['public_path'];
                
                // Clear both regular and quick signature caches
                $cacheKey1 = 'frame_signature_' . md5($framePhoto . filemtime($filePath));
                $cacheKey2 = 'frame_signature_quick_' . md5($framePhoto . filemtime($filePath));
                
                Cache::forget($cacheKey1);
                Cache::forget($cacheKey2);
                
                Log::info("Signature cache cleared", ['frame_photo' => $framePhoto]);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error clearing signature cache: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Membersihkan semua cache signature
     * 
     * @return bool
     */
    public function clearAllSignatureCache()
    {
        try {
            // Karena Laravel tidak memiliki cara untuk clear cache berdasarkan prefix,
            // kita perlu clear cache untuk setiap frame
            $frames = Frame::whereNotNull('frame_foto')->get();
            
            foreach ($frames as $frame) {
                $this->clearFrameSignatureCache($frame->frame_foto);
            }
            
            Log::info("All signature caches cleared");
            return true;
        } catch (\Exception $e) {
            Log::error('Error clearing all signature caches: ' . $e->getMessage());
            return false;
        }
    }
}