@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-home me-2"></i>Selamat Datang,
                        {{ Auth::user()->role === 'owner' ? 'Owner' : 'Karyawan' }}
                    </h4>
                    <div class="text-end">
                        <small>{{ now()->format('d M Y') }}</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Frame Kacamata Card --}}
                        <div class="col-md-3 col-sm-6">
                            <div class="card card-hover h-100 border-0 shadow-hover">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="icon-circle bg-primary-soft text-primary rounded-circle">
                                            <i class="fas fa-glasses fs-4"></i>
                                        </div>
                                        <span class="badge bg-primary text-white fs-6">
                                            {{ \App\Models\Frame::count() }}
                                        </span>
                                    </div>
                                    <h5 class="card-title mb-2">Frame Kacamata</h5>
                                    <p class="card-text text-muted mb-3">
                                        Kelola dan pantau data frame kacamata secara komprehensif.
                                    </p>
                                    <a href="{{ route('frame.index') }}" class="btn btn-outline-primary mt-auto">
                                        Kelola Frame <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Kriteria SPK Card --}}
                        <div class="col-md-3 col-sm-6">
                            <div class="card card-hover h-100 border-0 shadow-hover">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="icon-circle bg-success-soft text-success rounded-circle">
                                            <i class="fas fa-chart-pie fs-4"></i>
                                        </div>
                                        <span class="badge bg-success text-white fs-6">
                                            {{ \App\Models\Kriteria::count() }}
                                        </span>
                                    </div>
                                    <h5 class="card-title mb-2">Kriteria SPK</h5>
                                    <p class="card-text text-muted mb-3">
                                        Atur kriteria sistem pendukung keputusan untuk analisis mendalam.
                                    </p>
                                    <a href="{{ route('kriteria.index') }}" class="btn btn-outline-success mt-auto">
                                        Kelola Kriteria <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Subkriteria Card --}}
                        <div class="col-md-3 col-sm-6">
                            <div class="card card-hover h-100 border-0 shadow-hover">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="icon-circle bg-warning-soft text-warning rounded-circle">
                                            <i class="fas fa-layer-group fs-4"></i>
                                        </div>
                                        <span class="badge bg-warning text-white fs-6">
                                            {{ \App\Models\Subkriteria::count() }}
                                        </span>
                                    </div>
                                    <h5 class="card-title mb-2">Subkriteria</h5>
                                    <p class="card-text text-muted mb-3">
                                        Rinci dan spesifikasikan subkriteria untuk evaluasi lebih akurat.
                                    </p>
                                    <a href="{{ route('subkriteria.index') }}" class="btn btn-outline-warning mt-auto">
                                        Kelola Subkriteria <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Riwayat Rekomendasi Card --}}
                        <div class="col-md-3 col-sm-6">
                            <div class="card card-hover h-100 border-0 shadow-hover">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="icon-circle bg-info-soft text-info rounded-circle">
                                            <i class="fas fa-history fs-4"></i>
                                        </div>
                                        <span class="badge bg-info text-white fs-6">
                                            {{ \App\Models\RecommendationHistory::count() }}
                                        </span>
                                    </div>
                                    <h5 class="card-title mb-2">Riwayat Rekomendasi</h5>
                                    <p class="card-text text-muted mb-3">
                                        Telusuri dan analisis riwayat rekomendasi sebelumnya.
                                    </p>
                                    <a href="{{ route('rekomendasi.index') }}" class="btn btn-outline-info mt-auto">
                                        Lihat Riwayat <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Statistik Tambahan --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-light shadow-sm">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>SPK Metode Profile Matching SMART
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        </div>
                                        <div class="col-md-3 col-6 border-end">
                                            <p class="text-muted mb-0">Bla bla bla</p>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Gaya sebelumnya tetap ada */
    .border-end {
        border-right: 1px solid #dee2e6 !important;
    }
    @media (max-width: 768px) {
        .border-end {
            border-right: none !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
    }
</style>
@endpush