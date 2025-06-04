<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StorageService;

class StorageSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:sync 
                           {--init : Initialize storage directories}
                           {--sync-all : Sync all files from storage to public}
                           {--check : Check file synchronization status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage storage file synchronization between storage/app/public and public/storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('init')) {
            return $this->initializeStorage();
        }

        if ($this->option('sync-all')) {
            return $this->syncAllFiles();
        }

        if ($this->option('check')) {
            return $this->checkSyncStatus();
        }

        // Default: show available options
        $this->info('Available options:');
        $this->line('  --init      Initialize storage directories');
        $this->line('  --sync-all  Sync all files from storage to public');
        $this->line('  --check     Check synchronization status');
        $this->line('');
        $this->info('Example usage:');
        $this->line('  php artisan storage:sync --init');
        $this->line('  php artisan storage:sync --sync-all');
        $this->line('  php artisan storage:sync --check');

        return 0;
    }

    /**
     * Initialize storage directories
     */
    private function initializeStorage()
    {
        $this->info('Initializing storage directories...');
        
        try {
            StorageService::initializeStorageDirectories();
            $this->info('✅ Storage directories initialized successfully!');
            
            // Show created directories
            $this->line('');
            $this->info('Created/Verified directories:');
            foreach (StorageService::STORAGE_DIRECTORIES as $dir) {
                $storagePath = storage_path('app/public/' . $dir);
                $publicPath = public_path('storage/' . $dir);
                
                $storageExists = is_dir($storagePath) ? '✅' : '❌';
                $publicExists = is_dir($publicPath) ? '✅' : '❌';
                
                $this->line("  {$dir}:");
                $this->line("    Storage: {$storageExists} {$storagePath}");
                $this->line("    Public:  {$publicExists} {$publicPath}");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to initialize storage directories: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync all files from storage to public
     */
    private function syncAllFiles()
    {
        $this->info('Syncing all files from storage to public...');
        
        try {
            $results = StorageService::syncAllToPublic();
            
            $this->info('✅ File synchronization completed!');
            $this->line('');
            
            $totalFiles = 0;
            $totalSynced = 0;
            $totalFailed = 0;
            
            foreach ($results as $directory => $result) {
                $totalFiles += $result['total'];
                $totalSynced += $result['synced'];
                $totalFailed += $result['failed'];
                
                $this->line("📁 {$directory}:");
                $this->line("   Total files: {$result['total']}");
                $this->line("   Synced: {$result['synced']}");
                $this->line("   Failed: {$result['failed']}");
                $this->line('');
            }
            
            $this->info("📊 Summary:");
            $this->info("   Total files processed: {$totalFiles}");
            $this->info("   Successfully synced: {$totalSynced}");
            $this->info("   Failed: {$totalFailed}");
            
            if ($totalFailed > 0) {
                $this->warn("⚠️  Some files failed to sync. Check the logs for details.");
                return 1;
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to sync files: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Check synchronization status
     */
    private function checkSyncStatus()
    {
        $this->info('Checking synchronization status...');
        $this->line('');
        
        try {
            foreach (StorageService::STORAGE_DIRECTORIES as $directory) {
                $storagePath = storage_path('app/public/' . $directory);
                $publicPath = public_path('storage/' . $directory);
                
                $this->line("📁 {$directory}:");
                
                if (!is_dir($storagePath)) {
                    $this->line("   ❌ Storage directory missing: {$storagePath}");
                    continue;
                }
                
                if (!is_dir($publicPath)) {
                    $this->line("   ❌ Public directory missing: {$publicPath}");
                    continue;
                }
                
                $storageFiles = collect(\File::allFiles($storagePath))->map->getFilename()->sort();
                $publicFiles = collect(\File::allFiles($publicPath))->map->getFilename()->sort();
                
                $storageCount = $storageFiles->count();
                $publicCount = $publicFiles->count();
                $syncedCount = $storageFiles->intersect($publicFiles)->count();
                $missingInPublic = $storageFiles->diff($publicFiles);
                
                $this->line("   📊 Storage files: {$storageCount}");
                $this->line("   📊 Public files: {$publicCount}");
                $this->line("   ✅ Synchronized: {$syncedCount}");
                
                if ($missingInPublic->isNotEmpty()) {
                    $this->line("   ⚠️  Missing in public: {$missingInPublic->count()}");
                    if ($missingInPublic->count() <= 5) {
                        foreach ($missingInPublic as $file) {
                            $this->line("      - {$file}");
                        }
                    } else {
                        $this->line("      - (showing first 5)");
                        foreach ($missingInPublic->take(5) as $file) {
                            $this->line("      - {$file}");
                        }
                        $remaining = $missingInPublic->count() - 5;
                        $this->line("      - ... and {$remaining} more files");
                    }
                }
                
                $this->line('');
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to check sync status: ' . $e->getMessage());
            return 1;
        }
    }
}