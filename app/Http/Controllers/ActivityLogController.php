<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        // Set jumlah item per halaman (default 10)
        $perPage = $request->has('per_page') ? $request->per_page : 20;
        
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');
        
        // Filter berdasarkan modul
        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }
        
        // Filter berdasarkan aksi
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }
        
        // Filter berdasarkan user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $logs = $query->paginate($perPage);
        
        // Data untuk dropdown filter
        $users = \App\Models\User::where('role', 'karyawan')->get();
        $modules = ActivityLog::distinct('module')->pluck('module');
        $actions = ['create', 'update', 'delete'];
        
        return view('logs.index', compact('logs', 'users', 'modules', 'actions', 'perPage'));
    }
    
    public function show($id)
    {
        $log = ActivityLog::with('user')->findOrFail($id);
        return view('logs.show', compact('log'));
    }

    private function backupImageForLogs($originalPath) 
    { 
        // Skip if no image 
        if (!$originalPath) { 
            return null; 
        }
        
        // Make sure logs_archive directory exists 
        $logsArchiveDir = 'logs_archive'; 
        if (!Storage::disk('public')->exists($logsArchiveDir)) { 
            Storage::disk('public')->makeDirectory($logsArchiveDir); 
        }
        
        // Generate a unique name for the archived file 
        $fileName = 'log_' . time() . '_' . basename($originalPath); 
        $archivePath = $logsArchiveDir . '/' . $fileName;
        
        // Only proceed if original file exists 
        if (Storage::disk('public')->exists($originalPath)) { 
            // Copy the file to the logs archive 
            Storage::disk('public')->copy($originalPath, $archivePath);
            
            // Return the archive path 
            return $archivePath; 
        }
        
        return null; 
    }

    private function deleteLogArchivedImages($logData)
    {
        // Check if log data contains image backup information
        if (is_string($logData)) {
            $decodedData = json_decode($logData, true);
        } else {
            $decodedData = $logData;
        }
        
        // Process old data (before change)
        if (isset($decodedData['old_data'])) {
            $this->deleteArchivedImageFromData($decodedData['old_data']);
        }
        
        // Process new data (after change) if it exists
        if (isset($decodedData['new_data'])) {
            $this->deleteArchivedImageFromData($decodedData['new_data']);
        }
        
        // Direct log_image_backup in the main data
        $this->deleteArchivedImageFromData($decodedData);
    }

    // Helper method to extract archived image paths from data
    private function extractLogArchivedImagePaths($logData)
    {
        $imagePaths = [];
        
        if (is_string($logData)) {
            $decodedData = json_decode($logData, true);
        } else {
            $decodedData = $logData;
        }
        
        // Process old data (before change)
        if (isset($decodedData['old_data'])) {
            $paths = $this->extractArchivedImagePathsFromData($decodedData['old_data']);
            $imagePaths = array_merge($imagePaths, $paths);
        }
        
        // Process new data (after change) if it exists
        if (isset($decodedData['new_data'])) {
            $paths = $this->extractArchivedImagePathsFromData($decodedData['new_data']);
            $imagePaths = array_merge($imagePaths, $paths);
        }
        
        // Direct log_image_backup in the main data
        $paths = $this->extractArchivedImagePathsFromData($decodedData);
        $imagePaths = array_merge($imagePaths, $paths);
        
        return $imagePaths;
    }
    
    // Helper method to extract archived image paths recursively
    private function extractArchivedImagePathsFromData($data)
    {
        $paths = [];
        
        if (!is_array($data)) {
            return $paths;
        }
        
        // Check for log_image_backup field
        if (isset($data['log_image_backup']) && $data['log_image_backup']) {
            $paths[] = $data['log_image_backup'];
        }
        
        // Check if there might be nested data with images
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nestedPaths = $this->extractArchivedImagePathsFromData($value);
                $paths = array_merge($paths, $nestedPaths);
            }
        }
        
        return $paths;
    }
    
    // Helper method to extract and delete archived image paths (for backward compatibility)
    private function deleteArchivedImageFromData($data)
    {
        if (!is_array($data)) {
            return;
        }
        
        // Check for log_image_backup field
        if (isset($data['log_image_backup']) && $data['log_image_backup']) {
            $this->deleteArchiveImage($data['log_image_backup']);
        }
        
        // Check if there might be nested data with images
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->deleteArchivedImageFromData($value);
            }
        }
    }

    // Delete a single archived image
    private function deleteArchiveImage($path)
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    // Method to clean up any orphaned files in the logs_archive directory
    private function cleanOrphanedArchiveFiles()
    {
        $archiveDir = 'logs_archive';
        
        // Check if directory exists
        if (!Storage::disk('public')->exists($archiveDir)) {
            return;
        }
        
        // Get all files in the logs_archive directory and delete them
        $files = Storage::disk('public')->files($archiveDir);
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
    }
    
    // Method to clean up orphaned files by date pattern
    private function cleanOrphanedArchiveFilesByDatePattern($dateFrom, $dateTo)
    {
        $archiveDir = 'logs_archive';
        
        // Check if directory exists
        if (!Storage::disk('public')->exists($archiveDir)) {
            return;
        }
        
        // Convert dates to timestamps for comparison
        $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
        $toTimestamp = strtotime($dateTo . ' 23:59:59');
        
        // Get all files in the logs_archive directory
        $files = Storage::disk('public')->files($archiveDir);
        
        foreach ($files as $file) {
            // Extract timestamp from filename (assuming format: log_TIMESTAMP_filename)
            $filename = basename($file);
            if (preg_match('/log_(\d+)_/', $filename, $matches)) {
                $fileTimestamp = (int) $matches[1];
                
                // If file timestamp is within our range, delete it
                if ($fileTimestamp >= $fromTimestamp && $fileTimestamp <= $toTimestamp) {
                    Storage::disk('public')->delete($file);
                }
            }
        }
    }

    // Modified deleteAll method to use date range from request
    public function deleteAll(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ], [
                'date_from.required' => 'Tanggal awal harus diisi',
                'date_to.required' => 'Tanggal akhir harus diisi',
                'date_to.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal'
            ]);

            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            // Get logs in the date range
            $logs = ActivityLog::whereDate('created_at', '>=', $dateFrom)
                              ->whereDate('created_at', '<=', $dateTo)
                              ->get();
            
            // Collect all image paths that need to be deleted
            $imagesToDelete = [];
            
            // Extract image paths from each log's data
            foreach ($logs as $log) {
                if ($log->data) {
                    $imagePaths = $this->extractLogArchivedImagePaths($log->data);
                    $imagesToDelete = array_merge($imagesToDelete, $imagePaths);
                }
            }
            
            // Delete log entries in the date range
            $deletedCount = ActivityLog::whereDate('created_at', '>=', $dateFrom)
                                     ->whereDate('created_at', '<=', $dateTo)
                                     ->delete();
            
            // Delete the collected image files
            foreach ($imagesToDelete as $path) {
                $this->deleteArchiveImage($path);
            }
            
            // Check for and remove orphaned files if needed
            $this->cleanOrphanedArchiveFilesByDatePattern($dateFrom, $dateTo);
            
            return redirect()->route('logs.index')
                ->with('success', "Berhasil menghapus {$deletedCount} data log dari tanggal {$dateFrom} sampai {$dateTo}!");
        } catch (\Exception $e) {
            return redirect()->route('logs.index')
                ->with('error', 'Gagal menghapus data log: ' . $e->getMessage());
        }
    }
}