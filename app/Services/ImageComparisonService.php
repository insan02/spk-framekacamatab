<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use App\Models\Frame;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageComparisonService
{
    // Increased threshold for better matching tolerance
    const SIMILARITY_THRESHOLD = 25;
    
    // Slightly increased dimensions for better accuracy
    const COMPARE_WIDTH = 650;
    const COMPARE_HEIGHT = 650;
    
    /**
     * Mencari kesamaan gambar dengan gambar frame yang sudah ada
     * 
     * @param UploadedFile $uploadedImage
     * @param int $limit Optional limit to return multiple matches
     * @return array|Frame|null Frame yang cocok, atau array of frames jika limit > 1, atau null jika tidak ada yang cocok
     */
    public function findSimilarFrame(UploadedFile $uploadedImage, int $limit = 1)
    {
        try {
            // Buat signature dari gambar yang diunggah
            $uploadedSignature = $this->createImageSignature($uploadedImage);
            
            // Ambil semua frame dari database
            $frames = Frame::all();
            
            $matches = [];
            $matchScores = [];
            
            foreach ($frames as $frame) {
                // Jika frame tidak memiliki foto, lewati
                if (!$frame->frame_foto || !Storage::disk('public')->exists($frame->frame_foto)) {
                    continue;
                }
                
                // Buat path lengkap ke file gambar
                $framePath = storage_path('app/public/' . $frame->frame_foto);
                
                // Buat signature dari gambar frame
                $frameSignature = $this->createImageSignatureFromPath($framePath);
                
                // Bandingkan signatures
                $difference = $this->compareSignatures($uploadedSignature, $frameSignature);
                
                // Simpan score untuk sorting
                $matchScores[$frame->frame_id] = $difference;
                
                // Jika perbedaan di bawah threshold, tambahkan ke matches
                if ($difference < self::SIMILARITY_THRESHOLD) {
                    $matches[$frame->frame_id] = $frame;
                }
            }
            
            // Tidak ada kecocokan yang ditemukan
            if (empty($matches)) {
                return null;
            }
            
            // Sort matches berdasarkan similarity (terendah = paling mirip)
            asort($matchScores);
            
            // Filter matches berdasarkan limit
            $result = [];
            $count = 0;
            foreach (array_keys($matchScores) as $frameId) {
                if (isset($matches[$frameId])) {
                    $result[] = $matches[$frameId];
                    $count++;
                    
                    if ($count >= $limit) {
                        break;
                    }
                }
            }
            
            return $limit === 1 ? $result[0] : $result;
            
        } catch (\Exception $e) {
            Log::error('Error in image comparison: ' . $e->getMessage());
            return null;
        }
    }
    
    // Rest of the existing methods...
    
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
        // Resize gambar ke ukuran standar untuk perbandingan
        $image->resize(self::COMPARE_WIDTH, self::COMPARE_HEIGHT);
        
        // Konversi ke grayscale untuk mengurangi kompleksitas
        $image->greyscale();
        
        // Meningkatkan kontras untuk mempertajam fitur
        $image->contrast(10);
        
        // Simpan nilai-nilai pixel dalam array
        $signature = [];
        
        for ($y = 0; $y < self::COMPARE_HEIGHT; $y++) {
            for ($x = 0; $x < self::COMPARE_WIDTH; $x++) {
                // Ambil warna dari pixel
                $color = $image->pickColor($x, $y);
                
                // Dalam grayscale, semua channel (R,G,B) memiliki nilai yang sama, jadi kita ambil salah satu
                $signature[] = $color[0];
            }
        }
        
        return $signature;
    }
    
    /**
     * Bandingkan dua signature gambar dan hitung perbedaannya
     * 
     * @param array $signature1
     * @param array $signature2
     * @return float Nilai perbedaan (0 = identik, semakin besar = semakin berbeda)
     */
    protected function compareSignatures(array $signature1, array $signature2)
    {
        // Pastikan kedua signature memiliki ukuran yang sama
        if (count($signature1) !== count($signature2)) {
            return PHP_FLOAT_MAX; // Tidak dapat dibandingkan
        }
        
        // Hitung total perbedaan antara dua signature
        $difference = 0;
        $pixelCount = count($signature1);
        
        for ($i = 0; $i < $pixelCount; $i++) {
            // Hitung selisih absolut antara dua nilai pixel
            $difference += abs($signature1[$i] - $signature2[$i]);
        }
        
        // Normalisasi perbedaan (0-100)
        return ($difference / $pixelCount) * 400 / 855;
    }
}