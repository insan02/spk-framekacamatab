@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        {{-- Display incomplete frames warning --}}
        @if(!empty($incompleteFrames))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Penilaian Belum Bisa Dilakukan!</strong> Terdapat {{ count($incompleteFrames) }} frame yang datanya belum lengkap.
            <br>
            <a href="{{ route('frame.index') }}" class="btn btn-sm btn-warning mt-2">Lengkapi Data Frame</a>
        </div>
        @endif

        {{-- Display validation errors --}}
        @if($errors->has('frame_incomplete'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> {{ $errors->first('frame_incomplete') }}
            <a href="{{ route('frame.index') }}" class="btn btn-sm btn-danger mt-2">Lengkapi Data Frame</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('success'))
            <div data-success-message="{{ session('success') }}" style="display:none;"></div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {!! session('error') !!}
            </div>
        @endif

        <!-- Wizard Navigation -->
        <div class="wizard-progress mb-4">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100" id="wizard-progress-bar"></div>
            </div>
            <div class="wizard-steps d-flex justify-content-between mt-2">
                <div class="wizard-step active" id="step-1">
                    <div class="wizard-step-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="wizard-step-label">Pilih Pelanggan</div>
                </div>
                <div class="wizard-step" id="step-2">
                    <div class="wizard-step-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="wizard-step-label">Isi Penilaian</div>
                </div>
                <div class="wizard-step" id="step-3">
                    <div class="wizard-step-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="wizard-step-label">Hasil Rekomendasi</div>
                </div>
            </div>
        </div>

        <!-- Step 1: Customer Selection -->
        <div class="card shadow-sm mb-4" id="customerSelectionCard">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>Penilaian - Pilih Pelanggan
                </h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between">
                            <div class="input-group w-50">
                                <input type="text" id="searchCustomer" class="form-control" placeholder="Nama atau No HP">
                                <button class="btn btn-outline-primary" type="button" id="searchCustomerBtn">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                            @if(Auth::user()->role === 'karyawan')
                            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Tambah Pelanggan
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Customer Table -->
                <h5 class="mb-3"><i class="fas fa-users me-2"></i>Data Pelanggan Terbaru</h5>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="customerTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>No. HP</th>
                                <th>Alamat</th>
                                @if(Auth::user()->role === 'karyawan')
                                <th>Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            @forelse($customers as $index => $customer)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->phone }}</td>
                                <td>{{ $customer->address }}</td>
                                @if(Auth::user()->role === 'karyawan')
                                <td>
                                    <div class="btn-group">
                                    <button class="btn btn-sm {{ !empty($incompleteFrames) ? 'btn-secondary' : 'btn-primary' }} select-customer" 
                                        data-id="{{ $customer->customer_id }}"
                                        data-name="{{ $customer->name }}"
                                        data-phone="{{ $customer->phone }}"
                                        data-address="{{ $customer->address }}"
                                        {{ !empty($incompleteFrames) ? 'disabled title="Lengkapi data frame terlebih dahulu"' : '' }}>
                                        <i class="fas fa-clipboard-check"></i> Pilih
                                    </button>
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data pelanggan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                @if(isset($customers) && method_exists($customers, 'links'))
                    {{ $customers->links() }}
                @endif

                <!-- No Results Message -->
                <div id="noResults" class="alert alert-info" style="display: none;">
                    Pelanggan tidak ditemukan.
                </div>
            </div>
        </div>

        <!-- Selected Customer Info -->
        <div id="selectedCustomerCard" class="card shadow-sm mb-4" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-user me-2"></i>Data Pelanggan
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Nama:</strong> <span id="selectedCustomerName"></span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>No HP:</strong> <span id="selectedCustomerPhone"></span></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Alamat:</strong> <span id="selectedCustomerAddress"></span></p>
                    </div>
                </div>
                <input type="hidden" id="selectedCustomerId" name="customer_id">
                
            </div>
        </div>

        <!-- Step 2: Penilaian Form -->
        @if(Auth::user()->role === 'karyawan')
        <div id="penilaianCard" class="card shadow-sm" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>Pengisian Penilaian
                </h4>
            </div>
            <div class="card-body">
                <!-- Tambahkan informasi awal -->
                <div id="noPelangganAlert" class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Silakan pilih pelanggan terlebih dahulu dengan menekan tombol <strong>Penilaian</strong> pada tabel pelanggan di atas.
                </div>
                
                <div class="action-buttons mb-3" style="display: none;">
                    <button 
                        id="editPenilaianBtn" 
                        class="btn btn-warning me-2" 
                        style="display: none;"
                    >
                        <i class="fas fa-edit"></i> Edit Penilaian
                    </button>
                    <button 
                        id="simpanPenilaianBtn" 
                        class="btn btn-success" 
                        type="submit"
                        style="display: none;"
                    >
                        <i class="fas fa-save"></i> Simpan Rekomendasi
                    </button>
                </div>

        {{-- Main form for assessment --}}
        <form method="POST" action="{{ route('penilaian.process') }}" id="penilaianForm">
            @csrf
            <input type="hidden" name="customer_id" id="penilaianCustomerId">

            <div class="card mb-3">
                <div class="card-header bg-light"><strong>Kriteria Frame</strong></div>
                <div class="card-body">
                    @foreach($kriterias as $kriteria)
                    <div class="mb-4">
                        <h5>{{ $kriteria->kriteria_nama }}</h5>
                        <div class="row">
                            @foreach($kriteria->subkriterias as $subkriteria)
                            <div class="col-md-12 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input penilaian-input" type="radio" 
                                        name="subkriteria[{{ $kriteria->kriteria_id }}]" 
                                        value="{{ $subkriteria->subkriteria_id }}" 
                                        id="sub{{ $subkriteria->subkriteria_id }}" 
                                        required disabled>
                                    <label class="form-check-label" for="sub{{ $subkriteria->subkriteria_id }}">
                                        {{ $subkriteria->subkriteria_nama }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-light"><strong>Tingkat Kepentingan Bobot Kriteria</strong></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                @foreach($kriterias as $kriteria)
                                <div class="col-md-12 mb-3">
                                    <div class="form-group">
                                        <label>{{ $kriteria->kriteria_nama }}</label>
                                        <select name="bobot_kriteria[{{ $kriteria->kriteria_id }}]" 
                                               class="form-select form-select-sm bobot-kriteria penilaian-input" 
                                               required
                                               disabled>
                                            <option value="5" {{ ($kriteria->bobot ?? '5') == '5' ? 'selected' : '' }}>5 - Sangat Penting</option>
                                            <option value="4" {{ ($kriteria->bobot ?? '5') == '4' ? 'selected' : '' }}>4 - Penting</option>
                                            <option value="3" {{ ($kriteria->bobot ?? '5') == '3' ? 'selected' : '' }}>3 - Cukup Penting</option>
                                            <option value="2" {{ ($kriteria->bobot ?? '5') == '2' ? 'selected' : '' }}>2 - Kurang Penting</option>
                                            <option value="1" {{ ($kriteria->bobot ?? '5') == '1' ? 'selected' : '' }}>1 - Tidak Penting</option>
                                        </select>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div id="bobot-warning" class="alert alert-warning" style="display: none;">
                                Peringatan: Setiap kriteria harus memiliki bobot antara 1 dan 5
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header"><strong>Informasi Bobot</strong></div>
                                <div class="card-body">
                                    <p><strong>Bobot Kriteria</strong> merupakan tingkat kepentingan dari setiap kriteria dalam proses pengambilan keputusan.</p>
                                    <ul>
                                        <li><strong>5</strong> - Sangat Penting</li>
                                        <li><strong>4</strong> - Penting</li>
                                        <li><strong>3</strong> - Cukup Penting</li>
                                        <li><strong>2</strong> - Kurang Penting</li>
                                        <li><strong>1</strong> - Tidak Penting</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-3">
                <button type="button" id="prevToCustomerFromFormBtn" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
                
                <div>
                    <button 
                        type="button" 
                        id="batalEditBtn" 
                        class="btn btn-secondary" 
                        style="display: none;"
                    >
                        <i class="fas fa-times"></i> Batal Edit
                    </button>
                    
                    <button 
                        type="submit" 
                        id="submit-btn" 
                        class="btn btn-primary"
                        disabled
                    >
                        <i class="fas fa-calculator"></i> Proses Penilaian
                    </button>
                </div>
            </div>
        </form>
        </div>
        </div>

        <!-- Step 3: Hasil Penilaian -->
        <div id="hasilPenilaianCard" class="card shadow-sm mb-4" style="display: none;">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Hasil Penilaian
                </h4>
            </div>
            <div class="card-body">
                <div id="hasilPenilaianSection">
                    <div class="card">
                        <div class="card-body" id="hasilPenilaianContent">
                            {{-- Dynamic content will be loaded here --}}
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    
                    <div>
                        <button type="button" id="editFromResultBtn" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Edit Penilaian
                        </button>
                        
                        <button type="button" id="saveRecommendationBtn" class="btn btn-success">
                            <i class="fas fa-save"></i> Simpan Rekomendasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @else
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Fitur untuk memproses penilaian hanya tersedia untuk karyawan.
        </div>
        @endif
    </div>
</div>

<!-- Custom styles for wizard -->
<style>
    .wizard-progress {
        position: relative;
    }
    
    .wizard-steps {
        position: relative;
        z-index: 1;
    }
    
    .wizard-step {
        text-align: center;
        opacity: 0.5;
        transition: all 0.3s ease;
        width: 25%;
        position: relative;
    }
    
    .wizard-step.active {
        opacity: 1;
    }
    
    .wizard-step.completed {
        opacity: 1;
    }
    
    .wizard-step-icon {
        width: 40px;
        height: 40px;
        background-color: #e9ecef;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        transition: all 0.3s ease;
    }
    
    .wizard-step.active .wizard-step-icon {
        background-color: #0d6efd;
        color: white;
    }
    
    .wizard-step.completed .wizard-step-icon {
        background-color: #198754;
        color: white;
    }
    
    .wizard-step-label {
        font-size: 14px;
        font-weight: 500;
    }
</style>

<script src="{{ asset('js/penilaian.js') }}"></script>

@endsection