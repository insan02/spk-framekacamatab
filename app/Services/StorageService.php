<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class StorageService
{
    /**
     * Direktori utama storage
     */
    const STORAGE_DIRECTORIES = [
        'frames',
        'history_images', 
        'logs_archive',
        'temp'
    ];

    /**
     * Inisialisasi direktori storage dan pastikan ada di public/storage
     */
    public static function initializeStorageDirectories()
    {
        $publicStoragePath = public_path('storage');
        
        // Buat direktori public/storage jika belum ada
        if (!File::exists($publicStoragePath)) {
            File::makeDirectory($publicStoragePath, 0755, true);
            Log::info('Created public/storage directory');
        }

        // Buat setiap subdirektori
        foreach (self::STORAGE_DIRECTORIES as $directory) {
            $publicDir = $publicStoragePath . '/' . $directory;
            $storageDir = storage_path('app/public/' . $directory);

            // Pastikan direktori ada di storage/app/public
            if (!File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
                Log::info("Created storage directory: {$directory}");
            }

            // Pastikan direktori ada di public/storage
            if (!File::exists($publicDir)) {
                File::makeDirectory($publicDir, 0755, true);
                Log::info("Created public storage directory: {$directory}");
            }
        }
    }

    /**
     * Simpan file dan otomatis salin ke public/storage
     * IMPROVED VERSION with better error handling
     */
    public static function storeFile(UploadedFile $file, string $directory, ?string $filename = null)
    {
        try {
            // Inisialisasi direktori jika belum ada
            self::initializeStorageDirectories();

            // Generate filename jika tidak disediakan
            if (!$filename) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            }

            $relativePath = $directory . '/' . $filename;
            
            // Simpan ke storage/app/public
            $storagePath = $file->storeAs('public/' . $directory, $filename);
            
            if ($storagePath) {
                // Salin juga ke public/storage untuk akses langsung
                $sourceFile = storage_path('app/' . $storagePath);
                $destinationFile = public_path('storage/' . $relativePath);
                
                // Pastikan direktori tujuan ada
                $destinationDir = dirname($destinationFile);
                if (!File::exists($destinationDir)) {
                    File::makeDirectory($destinationDir, 0755, true);
                }
                
                // Retry mechanism untuk copy
                $copySuccess = false;
                $maxRetries = 3;
                
                for ($i = 0; $i < $maxRetries; $i++) {
                    if (File::copy($sourceFile, $destinationFile)) {
                        $copySuccess = true;
                        break;
                    }
                    
                    usleep(100000); // 0.1 second delay
                    Log::warning("Retry copying file to public directory", [
                        'attempt' => $i + 1,
                        'source' => $sourceFile,
                        'destination' => $destinationFile
                    ]);
                }
                
                // Verify final state
                $finalCheck = self::checkFileExistence($relativePath);
                
                if ($copySuccess) {
                    Log::info("File stored and copied successfully", [
                        'original_name' => $file->getClientOriginalName(),
                        'stored_path' => $relativePath,
                        'storage_location' => $sourceFile,
                        'public_location' => $destinationFile,
                        'synchronized' => $finalCheck['synchronized']
                    ]);
                } else {
                    Log::error("Failed to copy file to public storage after retries", [
                        'source' => $sourceFile,
                        'destination' => $destinationFile,
                        'storage_exists' => $finalCheck['storage_exists'],
                        'public_exists' => $finalCheck['public_exists']
                    ]);
                }
                
                return $relativePath;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error storing file: ' . $e->getMessage(), [
                'directory' => $directory,
                'filename' => $filename,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

   /**
     * Pindahkan file dari temporary ke direktori permanen
     * IMPROVED VERSION with better sync handling
     */
    public static function moveFromTemp(string $tempPath, string $permanentDirectory, ?string $newFilename = null)
    {
        try {
            self::initializeStorageDirectories();

            // Cek apakah file temp ada
            $fullTempPath = storage_path('app/public/' . $tempPath);
            $publicTempPath = public_path('storage/' . $tempPath);
            
            if (!File::exists($fullTempPath)) {
                Log::error("Temp file not found in storage", ['path' => $fullTempPath]);
                return false;
            }

            // Generate filename baru jika tidak disediakan
            if (!$newFilename) {
                $newFilename = time() . '_' . uniqid() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION);
            }

            $newRelativePath = $permanentDirectory . '/' . $newFilename;
            $newStoragePath = storage_path('app/public/' . $newRelativePath);
            $newPublicPath = public_path('storage/' . $newRelativePath);

            // Pastikan direktori tujuan ada
            $storageDir = dirname($newStoragePath);
            $publicDir = dirname($newPublicPath);
            
            if (!File::exists($storageDir)) {
                File::makeDirectory($storageDir, 0755, true);
            }
            if (!File::exists($publicDir)) {
                File::makeDirectory($publicDir, 0755, true);
            }

            // Pindahkan file dari temp ke permanent di storage
            if (File::move($fullTempPath, $newStoragePath)) {
                
                // CRITICAL: Pastikan file berhasil dipindahkan
                if (!File::exists($newStoragePath)) {
                    Log::error("File move succeeded but file doesn't exist at destination", [
                        'destination' => $newStoragePath
                    ]);
                    return false;
                }
                
                // Salin ke public/storage dengan retry mechanism
                $copySuccess = false;
                $maxRetries = 3;
                
                for ($i = 0; $i < $maxRetries; $i++) {
                    if (File::copy($newStoragePath, $newPublicPath)) {
                        $copySuccess = true;
                        break;
                    }
                    
                    // Small delay before retry
                    usleep(100000); // 0.1 second
                    Log::warning("Retry copying to public directory", [
                        'attempt' => $i + 1,
                        'source' => $newStoragePath,
                        'destination' => $newPublicPath
                    ]);
                }
                
                if (!$copySuccess) {
                    Log::error("Failed to copy moved file to public directory after retries", [
                        'source' => $newStoragePath,
                        'destination' => $newPublicPath
                    ]);
                    // File exists in storage but not in public - this is still partially successful
                }
                
                // Hapus file temp dari public jika ada
                if (File::exists($publicTempPath)) {
                    File::delete($publicTempPath);
                }

                // Verify final state
                $finalCheck = self::checkFileExistence($newRelativePath);
                
                Log::info("File moved from temp to permanent", [
                    'from' => $tempPath,
                    'to' => $newRelativePath,
                    'storage_exists' => $finalCheck['storage_exists'],
                    'public_exists' => $finalCheck['public_exists'],
                    'synchronized' => $finalCheck['synchronized']
                ]);

                return $newRelativePath;
            }

            Log::error("Failed to move file from temp to permanent", [
                'from' => $fullTempPath,
                'to' => $newStoragePath
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Error moving file from temp: ' . $e->getMessage(), [
                'temp_path' => $tempPath,
                'permanent_directory' => $permanentDirectory,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Hapus file dari storage dan public
     * 
     * @param string $relativePath
     * @return bool
     */
    public static function deleteFile(string $relativePath)
    {
        try {
            $storagePath = storage_path('app/public/' . $relativePath);
            $publicPath = public_path('storage/' . $relativePath);
            
            $deleted = true;
            
            // Hapus dari storage
            if (File::exists($storagePath)) {
                $deleted = File::delete($storagePath) && $deleted;
            }
            
            // Hapus dari public
            if (File::exists($publicPath)) {
                $deleted = File::delete($publicPath) && $deleted;
            }
            
            if ($deleted) {
                Log::info("File deleted successfully", ['path' => $relativePath]);
            }
            
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Error deleting file: ' . $e->getMessage(), ['path' => $relativePath]);
            return false;
        }
    }

    /**
     * Salin file yang sudah ada di storage ke public
     * IMPROVED VERSION with retry mechanism
     */
    public static function syncToPublic(string $relativePath)
    {
        try {
            $storagePath = storage_path('app/public/' . $relativePath);
            $publicPath = public_path('storage/' . $relativePath);
            
            if (!File::exists($storagePath)) {
                Log::warning("Source file not found for sync", ['path' => $storagePath]);
                return false;
            }
            
            // Pastikan direktori tujuan ada
            $publicDir = dirname($publicPath);
            if (!File::exists($publicDir)) {
                File::makeDirectory($publicDir, 0755, true);
            }
            
            // Retry mechanism
            $maxRetries = 3;
            
            for ($i = 0; $i < $maxRetries; $i++) {
                if (File::copy($storagePath, $publicPath)) {
                    Log::info("File synced to public", [
                        'path' => $relativePath,
                        'attempt' => $i + 1
                    ]);
                    return true;
                }
                
                usleep(100000); // 0.1 second delay
                Log::warning("Retry syncing file to public", [
                    'attempt' => $i + 1,
                    'path' => $relativePath
                ]);
            }
            
            Log::error("Failed to sync file to public after retries", [
                'path' => $relativePath,
                'storage_path' => $storagePath,
                'public_path' => $publicPath
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error syncing file to public: ' . $e->getMessage(), [
                'path' => $relativePath,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Sinkronisasi semua file dari storage ke public
     * 
     * @return array
     */
    public static function syncAllToPublic()
    {
        $results = [];
        
        try {
            self::initializeStorageDirectories();
            
            foreach (self::STORAGE_DIRECTORIES as $directory) {
                $storageDir = storage_path('app/public/' . $directory);
                $publicDir = public_path('storage/' . $directory);
                
                if (!File::exists($storageDir)) {
                    continue;
                }
                
                $files = File::allFiles($storageDir);
                $results[$directory] = [
                    'total' => count($files),
                    'synced' => 0,
                    'failed' => 0
                ];
                
                foreach ($files as $file) {
                    $relativePath = $directory . '/' . $file->getFilename();
                    
                    if (self::syncToPublic($relativePath)) {
                        $results[$directory]['synced']++;
                    } else {
                        $results[$directory]['failed']++;
                    }
                }
            }
            
            Log::info("Bulk sync completed", $results);
            
        } catch (\Exception $e) {
            Log::error('Error in bulk sync: ' . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Enhanced file existence check with more detailed info
     */
    public static function checkFileExistence(string $relativePath)
    {
        $storagePath = storage_path('app/public/' . $relativePath);
        $publicPath = public_path('storage/' . $relativePath);
        
        $storageExists = File::exists($storagePath);
        $publicExists = File::exists($publicPath);
        
        $result = [
            'storage_exists' => $storageExists,
            'public_exists' => $publicExists,
            'storage_path' => $storagePath,
            'public_path' => $publicPath,
            'synchronized' => $storageExists && $publicExists
        ];
        
        // Add file sizes for comparison if both exist
        if ($storageExists && $publicExists) {
            $result['storage_size'] = File::size($storagePath);
            $result['public_size'] = File::size($publicPath);
            $result['sizes_match'] = $result['storage_size'] === $result['public_size'];
        }
        
        return $result;
    }

    /**
     * Backup file ke history_images
     * 
     * @param string $originalPath
     * @param string $prefix
     * @return string|false
     */
    public static function backupFile(string $originalPath, string $prefix = 'backup')
    {
        try {
            $sourceStoragePath = storage_path('app/public/' . $originalPath);
            
            if (!File::exists($sourceStoragePath)) {
                Log::warning("Original file not found for backup", ['path' => $originalPath]);
                return false;
            }
            
            $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
            $backupFilename = $prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
            $backupRelativePath = 'history_images/' . $backupFilename;
            
            $backupStoragePath = storage_path('app/public/' . $backupRelativePath);
            $backupPublicPath = public_path('storage/' . $backupRelativePath);
            
            // Pastikan direktori backup ada
            $backupStorageDir = dirname($backupStoragePath);
            $backupPublicDir = dirname($backupPublicPath);
            
            if (!File::exists($backupStorageDir)) {
                File::makeDirectory($backupStorageDir, 0755, true);
            }
            if (!File::exists($backupPublicDir)) {
                File::makeDirectory($backupPublicDir, 0755, true);
            }
            
            // Salin file ke backup
            if (File::copy($sourceStoragePath, $backupStoragePath)) {
                // Salin juga ke public
                File::copy($backupStoragePath, $backupPublicPath);
                
                Log::info("File backed up successfully", [
                    'original' => $originalPath,
                    'backup' => $backupRelativePath
                ]);
                
                return $backupRelativePath;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Error backing up file: ' . $e->getMessage());
            return false;
        }
    }
}