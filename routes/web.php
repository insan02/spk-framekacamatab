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

// Redirect root ke login
Route::get('/', function () {
    return redirect()->route('login');
})->middleware('guest');

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Dashboard route
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('role:owner,karyawan');

    
    Route::middleware('role:owner,karyawan')->group(function () {
        Route::get('kriteria', [KriteriaController::class, 'index'])->name('kriteria.index');
        Route::get('subkriteria', [SubkriteriaController::class, 'index'])->name('subkriteria.index');
        Route::get('frame', [FrameController::class, 'index'])->name('frame.index');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
        Route::get('/password/edit', [ProfileController::class, 'editPassword'])->name('password.edit');
        Route::put('/password/update', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::get('penilaian', [PenilaianController::class, 'index'])->name('penilaian.index');
        Route::get('rekomendasi', [RekomendasiController::class, 'index'])->name('rekomendasi.index');
    });
    
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
        
        // Frames - create, store, edit, update, destroy
        Route::get('frame/create', [FrameController::class, 'create'])->name('frame.create');
        Route::post('frame', [FrameController::class, 'store'])->name('frame.store');
        Route::get('frame/{frame}/edit', [FrameController::class, 'edit'])->name('frame.edit');
        Route::put('frame/{frame}', [FrameController::class, 'update'])->name('frame.update');
        Route::delete('frame/{frame}', [FrameController::class, 'destroy'])->name('frame.destroy');

        // Tambahkan route ini di dalam group middleware auth
        Route::post('penilaian', [PenilaianController::class, 'store'])->name('penilaian.store');

        Route::get('rekomendasi', [RekomendasiController::class, 'index'])
            ->name('rekomendasi.index');
        
        Route::get('rekomendasi/{penilaian}', [RekomendasiController::class, 'show'])
            ->name('rekomendasi.show');
        
        Route::get('rekomendasi/{penilaian}/print', [RekomendasiController::class, 'print'])
            ->name('rekomendasi.print');
        
        Route::delete('rekomendasi/{penilaian}', [RekomendasiController::class, 'destroy'])
            ->name('rekomendasi.destroy');
    });

    
});