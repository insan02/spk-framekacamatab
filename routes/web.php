<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KriteriaController;
use App\Http\Controllers\SubkriteriaController;
use App\Http\Controllers\FrameController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PenilaianController;
use App\Http\Controllers\RekomendasiController;
use App\Http\Controllers\RecommendationHistoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
// Password Reset Routes
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])
    ->name('password.request');
    
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])
    ->name('password.email');
    
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])
    ->name('password.reset');
    
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->name('password.reset.update');
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:owner,karyawan');
    
    // Routes only for karyawan (CRUD operations except show)
    Route::middleware('role:karyawan')->group(function () {
        // Kriteria - create, store, edit, update, destroy
        Route::get('kriteria/create', [KriteriaController::class, 'create'])->name('kriteria.create');
        Route::post('kriteria', [KriteriaController::class, 'store'])->name('kriteria.store');
        Route::get('kriteria/{kriteria}/edit', [KriteriaController::class, 'edit'])->name('kriteria.edit');
        Route::put('kriteria/{kriteria}', [KriteriaController::class, 'update'])->name('kriteria.update');
        Route::delete('kriteria/{kriteria}', [KriteriaController::class, 'destroy'])->name('kriteria.destroy');
        
        // Subkriteria - create, store, edit, update, destroy
        Route::get('subkriteria/create', [SubkriteriaController::class, 'create'])->name('subkriteria.create');
        Route::post('subkriteria', [SubkriteriaController::class, 'store'])->name('subkriteria.store');
        Route::get('subkriteria/{subkriteria}/edit', [SubkriteriaController::class, 'edit'])->name('subkriteria.edit');
        Route::put('subkriteria/{subkriteria}', [SubkriteriaController::class, 'update'])->name('subkriteria.update');
        Route::delete('subkriteria/{subkriteria}', [SubkriteriaController::class, 'destroy'])->name('subkriteria.destroy');
        Route::delete('/subkriteria/reset/{kriteria_id}', [SubkriteriaController::class, 'resetSubkriteria'])
        ->name('subkriteria.reset');
        
        // Frames - create, store, edit, update, destroy
        Route::get('frame/create', [FrameController::class, 'create'])->name('frame.create');
        Route::post('frame', [FrameController::class, 'store'])->name('frame.store');
        // Add these routes in your routes/web.php
Route::get('/frame/confirm-duplicate/{similar_frame_id}', [FrameController::class, 'confirmDuplicate'])
->name('frame.confirm-duplicate');
Route::post('/frame/process-duplicate', [FrameController::class, 'processDuplicateConfirmation'])
->name('frame.process-duplicate');
        Route::get('frame/{frame}/edit', [FrameController::class, 'edit'])->name('frame.edit');
        Route::put('frame/{frame}', [FrameController::class, 'update'])->name('frame.update');
        Route::delete('frame/{frame}', [FrameController::class, 'destroy'])->name('frame.destroy');
        Route::get('/frames/{frame}/check-updates', [FrameController::class, 'checkUpdates'])->name('frame.checkUpdates');
        Route::get('/frame/batch-update', 'FrameController@batchUpdateForm')->name('frame.batchUpdateForm');
        Route::post('/frame/batch-update', 'FrameController@batchUpdate')->name('frame.batchUpdate');
        Route::get('/frames/needs-update', [FrameController::class, 'needsUpdate'])
            ->name('frame.needsUpdate');
        Route::delete('/frames/reset-kriteria', [FrameController::class, 'resetFrameKriteria'])->name('frame.reset-kriteria');
        Route::post('/frame/search-by-image', [FrameController::class, 'searchByImage'])->name('frame.searchByImage');

        // Tambahkan route ini di dalam group middleware auth
        Route::get('penilaian', [PenilaianController::class, 'index'])->name('penilaian.index');
        Route::post('penilaian', [PenilaianController::class, 'store'])->name('penilaian.store');
        Route::post('/penilaian/process', [PenilaianController::class, 'process'])->name('penilaian.process');
        Route::post('/penilaian/store', [PenilaianController::class, 'store'])
        ->name('penilaian.store');

        Route::delete('rekomendasi/{rekomendasi}', [RecommendationHistoryController::class, 'destroy'])->name('rekomendasi.destroy');
        
        });
    
    Route::middleware('role:owner')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('employees/create', [EmployeeController::class, 'create'])->name('employees.create');
        Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::get('employees/{employees}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
        Route::put('employees/{employees}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('employees/{employees}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });

    Route::middleware('role:owner,karyawan')->group(function () {
            Route::get('kriteria', [KriteriaController::class, 'index'])->name('kriteria.index');
            Route::get('subkriteria', [SubkriteriaController::class, 'index'])->name('subkriteria.index');
            Route::get('frame', [FrameController::class, 'index'])->name('frame.index');
            Route::get('/frame/{frame}', [FrameController::class, 'show'])->name('frame.show');
            Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
            Route::get('/password/edit', [ProfileController::class, 'editPassword'])->name('password.edit');
            Route::put('/password/update', [ProfileController::class, 'updatePassword'])->name('password.update');
            Route::get('rekomendasi', [RecommendationHistoryController::class, 'index'])->name('rekomendasi.index');
            Route::get('rekomendasi/{rekomendasi}', [RecommendationHistoryController::class, 'show'])->name('rekomendasi.show');
            Route::get('/rekomendasi/{id}', [RecommendationHistoryController::class, 'show'])->name('rekomendasi.show');
            Route::get('/rekomendasi/print/all', [RecommendationHistoryController::class, 'printAll'])->name('rekomendasi.print-all');
            Route::get('rekomendasi/print/{id}', [RecommendationHistoryController::class, 'print'])->name('rekomendasi.print');
            

      

    });
 
});