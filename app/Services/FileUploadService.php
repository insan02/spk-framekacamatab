<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    /**
     * Upload file langsung ke public/storage tanpa symbolic link
     * 
     * @param UploadedFile $file
     * @param string $folder
     * @param string|null $customName
     * @return string|false
     */
    public static function uploadToPublicStorage(UploadedFile $file, string $folder, ?string $customName = null)
    {
        try {
            // Tentukan nama file
            $fileName = $customName ?: time() . '.' . $file->extension();
            
            // Buat path tujuan
            $destinationPath = public_path('storage/' . $folder);
            
            // Pastikan folder tujuan ada
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            // Pindahkan file
            $file->move($destinationPath, $fileName);
            
            // Return path relatif
            return $folder . '/' . $fileName;
            
        } catch (\Exception $e) {
            Log::error('Error uploading file: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Pindahkan file dari temporary ke permanent location
     * 
     * @param string $tempPath (relatif dari public/storage)
     * @param string $permanentFolder
     * @param string|null $customName
     * @return string|false
     */
    public static function moveFromTemp(string $tempPath, string $permanentFolder, ?string $customName = null)
    {
        try {
            $sourcePath = public_path('storage/' . $tempPath);
            
            if (!File::exists($sourcePath)) {
                Log::error('Temp file not found: ' . $sourcePath);
                return false;
            }
            
            // Tentukan nama file baru
            $fileName = $customName ?: time() . '.jpg';
            
            // Buat path tujuan
            $destinationPath = public_path('storage/' . $permanentFolder);
            $destinationFile = $destinationPath . '/' . $fileName;
            
            // Pastikan folder tujuan ada
            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true);
            }
            
            // Pindahkan file
            File::move($sourcePath, $destinationFile);
            
            // Return path relatif
            return $permanentFolder . '/' . $fileName;
            
        } catch (\Exception $e) {
            Log::error('Error moving file from temp: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hapus file dari public/storage
     * 
     * @param string $filePath (relatif dari public/storage)
     * @return bool
     */
    public static function deleteFromPublicStorage(string $filePath)
    {
        try {
            $fullPath = public_path('storage/' . $filePath);
            
            if (File::exists($fullPath)) {
                File::delete($fullPath);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error('Error deleting file: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cek apakah file ada di public/storage
     * 
     * @param string $filePath (relatif dari public/storage)
     * @return bool
     */
    public static function existsInPublicStorage(string $filePath)
    {
        return File::exists(public_path('storage/' . $filePath));
    }
    
    /**
     * Copy file untuk backup
     * 
     * @param string $sourcePath (relatif dari public/storage)
     * @param string $backupFolder
     * @return string|false
     */
    public static function backupFile(string $sourcePath, string $backupFolder = 'backups')
    {
        try {
            $sourceFullPath = public_path('storage/' . $sourcePath);
            
            if (!File::exists($sourceFullPath)) {
                return false;
            }
            
            // Buat nama backup dengan timestamp
            $pathInfo = pathinfo($sourcePath);
            $backupFileName = $pathInfo['filename'] . '_backup_' . time() . '.' . $pathInfo['extension'];
            
            // Path backup
            $backupPath = public_path('storage/' . $backupFolder);
            
            // Pastikan folder backup ada
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }
            
            // Copy file
            File::copy($sourceFullPath, $backupPath . '/' . $backupFileName);
            
            // Return path relatif backup
            return $backupFolder . '/' . $backupFileName;
            
        } catch (\Exception $e) {
            Log::error('Error backing up file: ' . $e->getMessage());
            return false;
        }
    }
}