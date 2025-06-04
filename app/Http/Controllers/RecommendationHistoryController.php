<?php

namespace App\Http\Controllers;

use App\Models\RecommendationHistory;
use App\Services\FileUploadService; // Tambahkan import ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class RecommendationHistoryController extends Controller
{
    public function index()
    {
        $histories = RecommendationHistory::orderBy('created_at', 'asc')->paginate(10);
        return view('rekomendasi.index', compact('histories'));
    }

    public function show($id)
    {
        $history = RecommendationHistory::findOrFail($id);
        return view('rekomendasi.show', compact('history'));
    }

    public function print($id)
    {
        $history = RecommendationHistory::findOrFail($id);
        return view('rekomendasi.print', compact('history'));
    }

    // Helper method to extract image paths recursively from history data
    private function extractImagePathsFromHistoryData($data)
    {
        $paths = [];
        
        if (!is_array($data)) {
            // If it's a string that looks like an image path, add it
            if (is_string($data) && $this->looksLikeImagePath($data)) {
                $paths[] = $data;
            }
            return $paths;
        }
        
        // Check for common image field names that might exist in recommendation history
        $imageFields = ['image', 'foto', 'gambar', 'picture', 'photo', 'file_path', 'image_path', 'attachment', 'file'];
        
        foreach ($imageFields as $field) {
            if (isset($data[$field]) && $data[$field]) {
                // Handle both single image and array of images
                if (is_array($data[$field])) {
                    foreach ($data[$field] as $imagePath) {
                        if ($this->looksLikeImagePath($imagePath)) {
                            $paths[] = $imagePath;
                        }
                    }
                } else {
                    if ($this->looksLikeImagePath($data[$field])) {
                        $paths[] = $data[$field];
                    }
                }
            }
        }
        
        // Check all values for potential image paths
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedPaths = $this->extractImagePathsFromHistoryData($value);
                $paths = array_merge($paths, $nestedPaths);
            } elseif (is_string($value) && $this->looksLikeImagePath($value)) {
                $paths[] = $value;
            }
        }
        
        return $paths;
    }
    
    // Helper method to check if a string looks like an image path
    private function looksLikeImagePath($path)
    {
        if (!is_string($path) || empty($path)) {
            return false;
        }
        
        // Check for common image extensions
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return in_array($extension, $imageExtensions) || 
               str_contains($path, 'history_images/') ||
               str_contains($path, '/storage/') ||
               str_contains($path, 'uploads/');
    }
    
    // Delete a single image using FileUploadService
    private function deleteHistoryImage($path)
    {
        try {
            // Clean up the path
            $originalPath = $path;
            
            // Remove any leading slashes or storage prefixes
            $path = ltrim($path, '/');
            $path = str_replace(['storage/', 'public/'], '', $path);
            
            // Convert path to use history_images folder if it doesn't already include it
            if (!str_contains($path, 'history_images/')) {
                $path = 'history_images/' . $path;
            }
            
            Log::info("Attempting to delete image - Original: {$originalPath}, Final: {$path}");
            
            if (FileUploadService::existsInPublicStorage($path)) {
                if (FileUploadService::deleteFromPublicStorage($path)) {
                    Log::info('Successfully deleted history image: ' . $path);
                    return true;
                } else {
                    Log::error('Failed to delete history image via FileUploadService: ' . $path);
                    return false;
                }
            } else {
                // Try alternative paths
                $alternativePaths = [
                    $originalPath,
                    ltrim($originalPath, '/'),
                    'history_images/' . basename($originalPath),
                    str_replace(['storage/', 'public/'], 'history_images/', ltrim($originalPath, '/'))
                ];
                
                foreach ($alternativePaths as $altPath) {
                    if (FileUploadService::existsInPublicStorage($altPath)) {
                        if (FileUploadService::deleteFromPublicStorage($altPath)) {
                            Log::info('Successfully deleted history image with alternative path: ' . $altPath);
                            return true;
                        }
                    }
                }
                
                Log::warning('History image not found in any expected location: ' . $originalPath);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error deleting history image: ' . $path . ', Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all images associated with a history record
     * 
     * @param RecommendationHistory $history
     * @return int Number of images deleted
     */
    private function deleteHistoryImages($history)
    {
        $imagesToDelete = [];
        $deletedCount = 0;
        
        try {
            // Get all attributes of the history model
            $historyData = $history->toArray();
            
            Log::info("Processing history ID {$history->id} with data: " . json_encode($historyData));
            
            // Extract image paths from the history data
            $imagePaths = $this->extractImagePathsFromHistoryData($historyData);
            $imagesToDelete = array_merge($imagesToDelete, $imagePaths);
            
            // Check each field individually for debugging
            foreach ($historyData as $key => $value) {
                if (!empty($value)) {
                    // If it's a JSON string, try to decode it
                    if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
                        try {
                            $decodedData = json_decode($value, true);
                            if (is_array($decodedData)) {
                                $jsonImagePaths = $this->extractImagePathsFromHistoryData($decodedData);
                                $imagesToDelete = array_merge($imagesToDelete, $jsonImagePaths);
                                Log::info("Found JSON data in field {$key}: " . json_encode($decodedData));
                            }
                        } catch (\Exception $e) {
                            // Not valid JSON, check if it's a direct image path
                            if ($this->looksLikeImagePath($value)) {
                                $imagesToDelete[] = $value;
                                Log::info("Found direct image path in field {$key}: {$value}");
                            }
                        }
                    } elseif ($this->looksLikeImagePath($value)) {
                        $imagesToDelete[] = $value;
                        Log::info("Found image path in field {$key}: {$value}");
                    }
                }
            }
            
            // Remove duplicates and empty values
            $imagesToDelete = array_filter(array_unique($imagesToDelete));
            
            Log::info("Images to delete for history ID {$history->id}: " . json_encode($imagesToDelete));
            
            // Delete the collected image files
            foreach ($imagesToDelete as $path) {
                if ($this->deleteHistoryImage($path)) {
                    $deletedCount++;
                }
            }
            
            Log::info("Successfully deleted {$deletedCount} images for history ID {$history->id}");
            
        } catch (\Exception $e) {
            Log::error("Error deleting images for history ID {$history->id}: " . $e->getMessage());
        }
        
        return $deletedCount;
    }

    public function destroy($id)
    {
        try {
            $history = RecommendationHistory::findOrFail($id);
            
            // Delete associated images before deleting the record
            $deletedImages = $this->deleteHistoryImages($history);
            
            $history->delete();
            
            Log::info("Deleted recommendation history ID {$id} with {$deletedImages} associated images");
            
            return redirect()->route('rekomendasi.index')
                ->with('success', 'Riwayat rekomendasi berhasil dihapus bersama dengan ' . $deletedImages . ' file gambar terkait.');
                
        } catch (\Exception $e) {
            Log::error('Failed to delete recommendation history: ' . $e->getMessage());
            return redirect()->route('rekomendasi.index')
                ->with('error', 'Gagal menghapus riwayat rekomendasi: ' . $e->getMessage());
        }
    }

    public function printAll(Request $request)
    {
        // Get filter parameters and sanitize them
        $startDate = $request->input('start_date') ? date('Y-m-d', strtotime($request->input('start_date'))) : null;
        $endDate = $request->input('end_date') ? date('Y-m-d', strtotime($request->input('end_date'))) : null;
        
        // Base query
        $query = RecommendationHistory::query()->orderBy('created_at', 'asc');
        
        // Apply date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00', 
                $endDate . ' 23:59:59'
            ]);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        
        // Get all histories for the report
        $histories = $query->get();
        
        // Return print view
        return view('rekomendasi.print-all', [
            'histories' => $histories,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
    
    // Clean up orphaned files based on timestamp pattern in history_images folder
    private function cleanOrphanedHistoryFilesByDatePattern($dateFrom, $dateTo)
    {
        $historyDir = 'history_images'; // Folder untuk history images
        
        // Convert dates to timestamps for comparison
        $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
        $toTimestamp = strtotime($dateTo . ' 23:59:59');
        
        $deletedFiles = 0;
        
        try {
            // Get all files in the history_images directory using public_path
            $historyPath = public_path('storage/' . $historyDir);
            
            if (!is_dir($historyPath)) {
                Log::info("History images directory does not exist: {$historyPath}");
                return 0;
            }
            
            $files = glob($historyPath . '/*');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    
                    // Extract timestamp from filename (various patterns)
                    $patterns = [
                        '/_(\d{10})\./',           // _timestamp.ext
                        '/_(\d{10})_/',           // _timestamp_
                        '/(\d{10})\./',           // timestamp.ext
                        '/_backup_(\d+)\./',      // _backup_timestamp.ext (if same pattern as activity log)
                    ];
                    
                    $fileTimestamp = null;
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $filename, $matches)) {
                            $fileTimestamp = (int) $matches[1];
                            break;
                        }
                    }
                    
                    // If we found a timestamp and it's within our range, delete it
                    if ($fileTimestamp && $fileTimestamp >= $fromTimestamp && $fileTimestamp <= $toTimestamp) {
                        $relativePath = $historyDir . '/' . $filename;
                        if (FileUploadService::deleteFromPublicStorage($relativePath)) {
                            $deletedFiles++;
                            Log::info("Deleted orphaned history file: {$relativePath}");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error cleaning orphaned history files: " . $e->getMessage());
        }
        
        Log::info("Cleaned up {$deletedFiles} orphaned history files between {$dateFrom} and {$dateTo}");
        return $deletedFiles;
    }
    
    public function resetData(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date'
            ]);
            
            // Get filter parameters and sanitize them
            $startDate = $request->input('start_date') ? date('Y-m-d', strtotime($request->input('start_date'))) : null;
            $endDate = $request->input('end_date') ? date('Y-m-d', strtotime($request->input('end_date'))) : null;
            
            // Base query
            $query = RecommendationHistory::query();
            
            // Apply date filters if provided
            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    $startDate . ' 00:00:00', 
                    $endDate . ' 23:59:59'
                ]);
            } elseif ($startDate) {
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            } elseif ($endDate) {
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }
            
            // Get all histories that will be deleted to process their images
            $historiesToDelete = $query->get();
            
            // Initialize counters
            $count = $historiesToDelete->count();
            $imageCount = 0;
            
            // Process each history to delete associated images
            foreach ($historiesToDelete as $history) {
                $deletedImages = $this->deleteHistoryImages($history);
                $imageCount += $deletedImages;
            }
            
            // Delete the records from database
            $query->delete();
            
            // Clean up orphaned files if date range is specified
            $deletedOrphans = 0;
            if ($startDate && $endDate) {
                $deletedOrphans = $this->cleanOrphanedHistoryFilesByDatePattern($startDate, $endDate);
            }
            
            $totalDeletedImages = $imageCount + $deletedOrphans;
            
            $message = "Berhasil mereset $count data riwayat rekomendasi dan menghapus $totalDeletedImages file gambar terkait.";
            if ($startDate && $endDate) {
                $message .= " (Periode: $startDate sampai $endDate)";
            }
            
            Log::info("Reset recommendation history: {$count} records, {$totalDeletedImages} images deleted");
            
            return redirect()->route('rekomendasi.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Failed to reset recommendation history data: ' . $e->getMessage());
            return redirect()->route('rekomendasi.index')
                ->with('error', 'Gagal mereset data riwayat rekomendasi: ' . $e->getMessage());
        }
    }
}