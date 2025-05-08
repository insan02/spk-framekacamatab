<?php

namespace App\Services;

use Intervention\Image\ImageManagerStatic as Image;
use App\Models\Frame;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImageComparisonService
{
    // Adjusted threshold for better matching
    const SIMILARITY_THRESHOLD = 10;
    
    // Dimensions for edge detection and feature extraction
    const COMPARE_WIDTH = 256;
    const COMPARE_HEIGHT = 256;
    
    // Edge detection settings
    const EDGE_WIDTH = 64;
    const EDGE_HEIGHT = 64;
    
    // Number of chunks for batch processing
    const CHUNK_SIZE = 10;
    
    // Number of rotations to try (improve angle detection)
    const ROTATION_ANGLES = [-15, -10, -5, 0, 5, 10, 15];
    
    /**
     * Find similar frames to the uploaded image
     * 
     * @param UploadedFile $uploadedImage
     * @param int $limit Optional limit to return multiple matches
     * @return array|Frame|null Matching frame, array of frames if limit > 1, or null if no match
     */
    public function findSimilarFrame(UploadedFile $uploadedImage, int $limit = 1)
    {
        try {
            // Create enhanced edge map and histogram signatures
            $uploadedSignatures = $this->createImageSignatures($uploadedImage);
            
            // Get frame count
            $totalFrames = Frame::count();
            
            $matches = [];
            $matchScores = [];
            
            // Process frames in chunks to avoid memory issues
            $processedFrames = 0;
            
            while ($processedFrames < $totalFrames) {
                // Get frames in chunks
                $frames = Frame::skip($processedFrames)
                               ->take(self::CHUNK_SIZE)
                               ->get();
                
                foreach ($frames as $frame) {
                    // Skip if frame has no photo
                    if (!$frame->frame_foto || !Storage::disk('public')->exists($frame->frame_foto)) {
                        continue;
                    }
                    
                    // Get full path to image file
                    $framePath = storage_path('app/public/' . $frame->frame_foto);
                    
                    // Create signatures from frame image
                    $frameSignatures = $this->createImageSignaturesFromPath($framePath);
                    
                    // Find the best match across all rotation angles
                    $bestDifference = PHP_FLOAT_MAX;
                    
                    foreach (self::ROTATION_ANGLES as $angleIndex => $angle) {
                        // Compare edge signatures for this angle
                        $edgeDifference = $this->compareEdgeSignatures(
                            $uploadedSignatures['edges'][3], // Use original angle (index 3 = 0 degrees) as reference
                            $frameSignatures['edges'][$angleIndex]
                        );
                        
                        // Compare color signatures (with lower weight)
                        $colorDifference = $this->compareColorHistograms(
                            $uploadedSignatures['color'][3], // Use original angle (index 3 = 0 degrees) as reference
                            $frameSignatures['color'][$angleIndex]
                        );
                        
                        // Combined difference score (weighted)
                        $difference = ($edgeDifference * 0.7) + ($colorDifference * 0.3);
                        
                        if ($difference < $bestDifference) {
                            $bestDifference = $difference;
                        }
                    }
                    
                    // Store best score for sorting
                    $matchScores[$frame->frame_id] = $bestDifference;
                    
                    // If difference is below threshold, add to matches
                    if ($bestDifference < self::SIMILARITY_THRESHOLD) {
                        $matches[$frame->frame_id] = $frame;
                    }
                }
                
                $processedFrames += self::CHUNK_SIZE;
            }
            
            // No matches found
            if (empty($matches)) {
                return null;
            }
            
            // Sort matches by similarity (lowest = most similar)
            asort($matchScores);
            
            // Filter matches based on limit
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
    
    /**
     * Create signatures from uploaded image file
     * 
     * @param UploadedFile $file
     * @return array
     */
    public function createImageSignatures(UploadedFile $file)
    {
        try {
            // Use Intervention Image to manipulate image
            $image = Image::make($file);
            
            return $this->processImageToSignatures($image);
        } catch (\Exception $e) {
            Log::error('Error creating image signatures: ' . $e->getMessage());
            // Return default empty signatures structure to avoid errors
            return [
                'edges' => array_fill(0, count(self::ROTATION_ANGLES), []),
                'color' => array_fill(0, count(self::ROTATION_ANGLES), [])
            ];
        }
    }
    
    /**
     * Create signatures from image file by path
     * 
     * @param string $path
     * @return array
     */
    public function createImageSignaturesFromPath(string $path)
    {
        try {
            // Use Intervention Image to manipulate image
            $image = Image::make($path);
            
            return $this->processImageToSignatures($image);
        } catch (\Exception $e) {
            Log::error('Error creating image signatures from path: ' . $e->getMessage());
            // Return default empty signatures structure to avoid errors
            return [
                'edges' => array_fill(0, count(self::ROTATION_ANGLES), []),
                'color' => array_fill(0, count(self::ROTATION_ANGLES), [])
            ];
        }
    }
    
    /**
     * Process image and create signatures including edge maps and color histograms
     * at different rotation angles
     * 
     * @param \Intervention\Image\Image $image
     * @return array
     */
    protected function processImageToSignatures($image)
    {
        // Create edge maps and color histograms at different angles
        $edgeSignatures = [];
        $colorHistograms = [];
        
        foreach (self::ROTATION_ANGLES as $angle) {
            $rotatedImage = clone $image;
            
            if ($angle !== 0) {
                $rotatedImage->rotate($angle);
            }
            
            // Create edge map
            $edgeMap = $this->createEdgeMap($rotatedImage);
            $edgeSignatures[] = $edgeMap;
            
            // Create color histogram
            $colorHistogram = $this->createColorHistogram($rotatedImage);
            $colorHistograms[] = $colorHistogram;
        }
        
        return [
            'edges' => $edgeSignatures, // Store all edge signatures for different angles
            'color' => $colorHistograms,
        ];
    }
    
    /**
     * Create edge map for image
     * 
     * @param \Intervention\Image\Image $image
     * @return array
     */
    protected function createEdgeMap($image)
    {
        // Clone and resize for edge detection
        $edgeImage = clone $image;
        $edgeImage->resize(self::EDGE_WIDTH, self::EDGE_HEIGHT);
        $edgeImage->greyscale();
        
        // Apply contrast and sharpening for better edge detection
        $edgeImage->contrast(20);
        $edgeImage->sharpen(15);
        
        // Create edge map using Sobel operator
        $edgeMap = [];
        
        // Calculate gradient magnitude using Sobel operator
        for ($y = 1; $y < self::EDGE_HEIGHT - 1; $y++) {
            for ($x = 1; $x < self::EDGE_WIDTH - 1; $x++) {
                // Get surrounding pixels
                $tl = $edgeImage->pickColor($x-1, $y-1)[0];
                $t = $edgeImage->pickColor($x, $y-1)[0];
                $tr = $edgeImage->pickColor($x+1, $y-1)[0];
                $l = $edgeImage->pickColor($x-1, $y)[0];
                $r = $edgeImage->pickColor($x+1, $y)[0];
                $bl = $edgeImage->pickColor($x-1, $y+1)[0];
                $b = $edgeImage->pickColor($x, $y+1)[0];
                $br = $edgeImage->pickColor($x+1, $y+1)[0];
                
                // Sobel X gradient
                $gx = ($tr + 2*$r + $br) - ($tl + 2*$l + $bl);
                
                // Sobel Y gradient
                $gy = ($bl + 2*$b + $br) - ($tl + 2*$t + $tr);
                
                // Gradient magnitude
                $mag = sqrt($gx*$gx + $gy*$gy);
                
                // Threshold to binary edge
                $edgeMap[] = ($mag > 30) ? 1 : 0;
            }
        }
        
        return $edgeMap;
    }
    
    /**
     * Create color histogram for entire image
     * 
     * @param \Intervention\Image\Image $image
     * @return array
     */
    protected function createColorHistogram($image)
    {
        // Clone and resize for color histogram
        $colorImage = clone $image;
        $colorImage->resize(self::COMPARE_WIDTH, self::COMPARE_HEIGHT);
        
        // Use more color bins for better accuracy
        $histR = array_fill(0, 8, 0);
        $histG = array_fill(0, 8, 0);
        $histB = array_fill(0, 8, 0);
        
        // Sample pixels at regular intervals
        $sampleStep = 4; // Sample every 4th pixel
        
        for ($y = 0; $y < self::COMPARE_HEIGHT; $y += $sampleStep) {
            for ($x = 0; $x < self::COMPARE_WIDTH; $x += $sampleStep) {
                $color = $colorImage->pickColor($x, $y);
                
                // Red channel
                $bucket = floor($color[0] * 8 / 256);
                $histR[$bucket]++;
                
                // Green channel
                $bucket = floor($color[1] * 8 / 256);
                $histG[$bucket]++;
                
                // Blue channel
                $bucket = floor($color[2] * 8 / 256);
                $histB[$bucket]++;
            }
        }
        
        // Normalize histograms
        $total = array_sum($histR);
        if ($total > 0) {
            $histR = array_map(function($val) use ($total) {
                return $val / $total;
            }, $histR);
            
            $histG = array_map(function($val) use ($total) {
                return $val / $total;
            }, $histG);
            
            $histB = array_map(function($val) use ($total) {
                return $val / $total;
            }, $histB);
        }
        
        return array_merge($histR, $histG, $histB);
    }
    
    /**
     * Compare two edge signatures and calculate the difference
     * 
     * @param array $edgeMap1
     * @param array $edgeMap2
     * @return float Difference value (0 = identical, higher = more different)
     */
    protected function compareEdgeSignatures(array $edgeMap1, array $edgeMap2)
    {
        // Make sure both signatures have the same size
        if (count($edgeMap1) !== count($edgeMap2)) {
            return PHP_FLOAT_MAX; // Cannot compare
        }
        
        // Calculate Hamming distance (number of bits that differ)
        $distance = 0;
        $totalEdges = 0;
        
        for ($i = 0; $i < count($edgeMap1); $i++) {
            if ($edgeMap1[$i] !== $edgeMap2[$i]) {
                $distance++;
            }
            if ($edgeMap1[$i] == 1 || $edgeMap2[$i] == 1) {
                $totalEdges++;
            }
        }
        
        // Normalize by the total number of edge pixels in both images
        return $totalEdges > 0 ? ($distance / $totalEdges) * 100 : 0;
    }
    
    /**
     * Compare two color histograms and calculate the difference
     * 
     * @param array $hist1
     * @param array $hist2
     * @return float Difference value (0 = identical, higher = more different)
     */
    protected function compareColorHistograms(array $hist1, array $hist2)
    {
        // Make sure both histograms have the same size
        if (count($hist1) !== count($hist2)) {
            return PHP_FLOAT_MAX; // Cannot compare
        }
        
        // Calculate histogram intersection distance
        $intersection = 0;
        
        for ($i = 0; $i < count($hist1); $i++) {
            $intersection += min($hist1[$i], $hist2[$i]);
        }
        
        // Convert intersection to distance (0-100)
        return (1 - $intersection) * 100;
    }
}