<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\FileUploadService; // Tambahkan import ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
    
    // Delete a single archived image using FileUploadService
    private function deleteArchiveImage($path)
    {
        try {
            if (FileUploadService::existsInPublicStorage($path)) {
                if (FileUploadService::deleteFromPublicStorage($path)) {
                    Log::info('Deleted archive image: ' . $path);
                    return true;
                } else {
                    Log::error('Failed to delete archive image via FileUploadService: ' . $path);
                    return false;
                }
            }
            Log::info('Archive image not found: ' . $path);
            return false;
        } catch (\Exception $e) {
            Log::error('Error deleting archive image: ' . $path . ', Error: ' . $e->getMessage());
            return false;
        }
    }

    // Delete a specific log entry and its archived images
    public function destroy($id)
    {
        try {
            // Find the log entry
            $log = ActivityLog::findOrFail($id);
            
            // Extract image paths to delete
            $imagesToDelete = [];
            
            // Check old_values field
            if ($log->old_values) {
                $oldValues = json_decode($log->old_values, true);
                if (is_array($oldValues)) {
                    $imagePaths = $this->extractArchivedImagePathsFromData($oldValues);
                    $imagesToDelete = array_merge($imagesToDelete, $imagePaths);
                }
            }
            
            // Check new_values field
            if ($log->new_values) {
                $newValues = json_decode($log->new_values, true);
                if (is_array($newValues)) {
                    $imagePaths = $this->extractArchivedImagePathsFromData($newValues);
                    $imagesToDelete = array_merge($imagesToDelete, $imagePaths);
                }
            }
            
            // Delete log entry
            $log->delete();
            
            // Delete the associated images using FileUploadService
            $deletedImages = 0;
            foreach (array_unique($imagesToDelete) as $path) {
                if ($this->deleteArchiveImage($path)) {
                    $deletedImages++;
                }
            }
            
            Log::info("Deleted log entry ID {$id} with {$deletedImages} associated images");
            
            return redirect()->route('logs.index')
                ->with('success', 'Log aktivitas berhasil dihapus.');
                
        } catch (\Exception $e) {
            Log::error('Failed to delete log entry: ' . $e->getMessage());
            return redirect()->route('logs.index')
                ->with('error', 'Gagal menghapus log: ' . $e->getMessage());
        }
    }

    // Clean up archived files based on timestamp pattern
    private function cleanOrphanedArchiveFilesByDatePattern($dateFrom, $dateTo)
    {
        $archiveDir = 'backups'; // Sesuaikan dengan folder backup default di FileUploadService
        
        // Convert dates to timestamps for comparison
        $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
        $toTimestamp = strtotime($dateTo . ' 23:59:59');
        
        $deletedFiles = 0;
        
        try {
            // Get all files in the backup directory using public_path
            $backupPath = public_path('storage/' . $archiveDir);
            
            if (!is_dir($backupPath)) {
                Log::info("Backup directory does not exist: {$backupPath}");
                return 0;
            }
            
            $files = glob($backupPath . '/*');
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    
                    // Extract timestamp from filename (format: filename_backup_TIMESTAMP.ext)
                    if (preg_match('/_backup_(\d+)\./', $filename, $matches)) {
                        $fileTimestamp = (int) $matches[1];
                        
                        // If file timestamp is within our range, delete it
                        if ($fileTimestamp >= $fromTimestamp && $fileTimestamp <= $toTimestamp) {
                            $relativePath = $archiveDir . '/' . $filename;
                            if (FileUploadService::deleteFromPublicStorage($relativePath)) {
                                $deletedFiles++;
                                Log::info("Deleted orphaned backup file: {$relativePath}");
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error cleaning orphaned files: " . $e->getMessage());
        }
        
        Log::info("Cleaned up {$deletedFiles} orphaned backup files between {$dateFrom} and {$dateTo}");
        return $deletedFiles;
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
                // Extract from old_values field
                if ($log->old_values) {
                    $oldData = json_decode($log->old_values, true);
                    if (is_array($oldData)) {
                        $paths = $this->extractArchivedImagePathsFromData($oldData);
                        $imagesToDelete = array_merge($imagesToDelete, $paths);
                    }
                }
                
                // Extract from new_values field
                if ($log->new_values) {
                    $newData = json_decode($log->new_values, true);
                    if (is_array($newData)) {
                        $paths = $this->extractArchivedImagePathsFromData($newData);
                        $imagesToDelete = array_merge($imagesToDelete, $paths);
                    }
                }
            }
            
            // Delete log entries in the date range
            $deletedCount = ActivityLog::whereDate('created_at', '>=', $dateFrom)
                                     ->whereDate('created_at', '<=', $dateTo)
                                     ->delete();
            
            // Delete the collected image files using FileUploadService
            $deletedImages = 0;
            foreach (array_unique($imagesToDelete) as $path) {
                if ($this->deleteArchiveImage($path)) {
                    $deletedImages++;
                }
            }
            
            // Check for and remove orphaned files by date pattern
            $deletedOrphans = $this->cleanOrphanedArchiveFilesByDatePattern($dateFrom, $dateTo);
            
            $totalDeletedImages = $deletedImages + $deletedOrphans;
            
            Log::info("Bulk delete completed: {$deletedCount} logs, {$totalDeletedImages} images from {$dateFrom} to {$dateTo}");
            
            return redirect()->route('logs.index')
                ->with('success', "Berhasil menghapus {$deletedCount} data log dengan {$totalDeletedImages} file gambar dari tanggal {$dateFrom} sampai {$dateTo}!");
                
        } catch (\Exception $e) {
            Log::error('Failed to bulk delete logs: ' . $e->getMessage());
            return redirect()->route('logs.index')
                ->with('error', 'Gagal menghapus data log: ' . $e->getMessage());
        }
    }
}