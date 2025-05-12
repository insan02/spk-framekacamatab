@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-home me-2"></i>Selamat Datang
                        {{ Auth::user()->role === 'owner' ? ', Owner' : '' }}

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
                                    <h5 class="card-title mb-2">Alternatif Frame Kacamata</h5>
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
                                    <h5 class="card-title mb-2">Kriteria</h5>
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
                                    <h5 class="card-title mb-2">Riwayat Penilaian</h5>
                                    <p class="card-text text-muted mb-3">
                                        Telusuri dan analisis riwayat penilaian rekomendasi sebelumnya.
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
                                        <div class="col-md-12 mb-3">
                                            <h6 class="text-primary">Menu Sistem</h6>
                                        </div>
                                        <div class="col-md-3 col-6 border-end">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-chart-pie text-success"></i>
                                            </div>
                                            <h6>Menu Kriteria</h6>
                                            <p class="text-muted mb-3">Mengelola data kriteria SPK termasuk nama, bobot, dan jenis kriteria. Memungkinkan penetapan parameter evaluasi untuk rekomendasi yang akurat.</p>
                                        </div>
                                        <div class="col-md-3 col-6 border-end">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-layer-group text-warning"></i>
                                            </div>
                                            <h6>Menu Subkriteria</h6>
                                            <p class="text-muted mb-3">Mengelola nilai dan parameter subkriteria dari setiap kriteria utama. Memungkinkan penentuan skala nilai yang lebih terperinci untuk proses evaluasi.</p>
                                        </div>
                                        <div class="col-md-3 col-6 border-end">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-glasses text-primary"></i>
                                            </div>
                                            <h6>Menu Alternatif</h6>
                                            <p class="text-muted mb-3">Mengelola data frame kacamata sebagai alternatif SPK. Termasuk fitur tambah, edit, hapus dan detail spesifikasi setiap frame.</p>
                                        </div>
                                        <div class="col-md-3 col-6">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-calculator text-danger"></i>
                                            </div>
                                            <h6>Menu Penilaian</h6>
                                            <p class="text-muted mb-3">Melakukan proses penilaian dengan metode Profile Matching SMART untuk mendapatkan rekomendasi frame terbaik sesuai kriteria yang ditentukan.</p>
                                        </div>
                                        <div class="col-md-3 col-6 border-end mt-4">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-history text-info"></i>
                                            </div>
                                            <h6>Riwayat Penilaian</h6>
                                            <p class="text-muted mb-3">Melihat dan mencetak laporan hasil penilaian SPK sebelumnya, memudahkan untuk melacak dan membandingkan rekomendasi yang telah diberikan.</p>
                                        </div>
                                        <div class="col-md-3 col-6 border-end mt-4">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-users-cog text-secondary"></i>
                                            </div>
                                            <h6>Kelola Akun</h6>
                                            <p class="text-muted mb-3">Khusus untuk owner. Mengelola akun karyawan termasuk menambah, mengedit, dan menonaktifkan akun pengguna sistem.</p>
                                        </div>
                                        <div class="col-md-3 col-6 border-end mt-4">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-clipboard-list text-purple"></i>
                                            </div>
                                            <h6>Log Aktivitas</h6>
                                            <p class="text-muted mb-3">Mencatat dan menampilkan aktivitas pengguna dalam sistem. Memudahkan pemantauan dan audit terhadap perubahan yang dilakukan.</p>
                                        </div>
                                        <div class="col-md-3 col-6 mt-4">
                                            <div class="feature-icon mb-2">
                                                <i class="fas fa-lightbulb text-warning"></i>
                                            </div>
                                            <h6>SPK Cerdas</h6>
                                            <p class="text-muted mb-3">Sistem mengimplementasikan metode Profile Matching dan SMART untuk rekomendasi frame kacamata yang optimal dan sesuai kebutuhan pelanggan.</p>
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
    .feature-icon {
        font-size: 1.75rem;
        height: 3rem;
        width: 3rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.5rem;
        border-radius: 50%;
    }
    h6 {
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
    .text-purple {
        color: #6f42c1;
    }
</style>
@endpush