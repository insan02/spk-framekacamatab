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

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-check me-2"></i>Penilaian 
                </h4>
            </div>
            <div class="card-body">
                <div>
                    <button 
                        id="editPenilaianBtn" 
                        class="btn btn-warning me-2" 
                        style="display: none;"
                    >
                        Edit Penilaian
                    </button>
                    <button 
                        id="simpanPenilaianBtn" 
                        class="btn btn-success" 
                        type="submit"
                        style="display: none;"
                    >
                        Simpan Rekomendasi
                    </button>
                </div>
    
                {{-- Main form for assessment --}}
                <form method="POST" action="{{ route('penilaian.process') }}" id="penilaianForm">
                    @csrf
                    <div class="card mb-3">
                        <div class="card-header"><strong>Data Pelanggan</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Nama Pelanggan</label>
                                        <input type="text" name="nama_pelanggan" class="form-control" pattern="[A-Za-z\s]+" title="Hanya huruf yang diperbolehkan" required>                    
                                        <span class="invalid-feedback" role="alert">      
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nohp_pelanggan">No HP</label>
                                        <div class="input-group">
                                            <input 
                                                type="tel" 
                                                name="nohp_pelanggan" 
                                                id="nohp_pelanggan"
                                                class="form-control" 
                                                pattern="[0-9]{9,13}" 
                                                maxlength="13"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                                required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Alamat</label>
                                        <input type="text" name="alamat_pelanggan" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><strong>Kriteria Frame</strong></div>
                        <div class="card-body">
                            @foreach($kriterias as $kriteria)
                            <div class="mb-4">
                                <h5>{{ $kriteria->kriteria_nama }}</h5>
                                <div class="row">
                                    @foreach($kriteria->subkriterias as $subkriteria)
                                    <div class="col-md-12 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" 
                                                name="subkriteria[{{ $kriteria->kriteria_id }}]" 
                                                value="{{ $subkriteria->subkriteria_id }}" 
                                                id="sub{{ $subkriteria->subkriteria_id }}" required>
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
                        <div class="card-header"><strong>Bobot Kriteria</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row">
                                        @foreach($kriterias as $kriteria)
                                        <div class="col-md-12 mb-3">
                                            <div class="form-group">
                                                <label>{{ $kriteria->kriteria_nama }}</label>
                                                <div class="input-group">
                                                    <input type="number" 
                                                        name="bobot_kriteria[{{ $kriteria->kriteria_id }}]" 
                                                        class="form-control form-control-sm bobot-kriteria" 
                                                        min="1" 
                                                        max="100" 
                                                        required
                                                        value="{{ $kriteria->bobot ?? '100' }}">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div id="bobot-warning" class="alert alert-warning" style="display: none;">
                                        Peringatan: Setiap kriteria harus memiliki bobot antara 1 dan 100
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-header"><strong>Informasi Bobot</strong></div>
                                        <div class="card-body">
                                            <p><strong>Bobot Kriteria</strong> merupakan tingkat kepentingan dari setiap kriteria dalam proses pengambilan keputusan.</p>
                                            <ul>
                                                <li><strong>Rentang nilai:</strong> 1-100</li>
                                            </ul>
                                            <p>Semakin tinggi nilai bobot, semakin besar pengaruh kriteria tersebut dalam perhitungan akhir.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            <button 
                                type="submit" 
                                id="submit-btn" 
                                class="btn btn-primary me-2"
                                @if(!empty($incompleteFrames)) data-incomplete="true" disabled @endif
                            >
                                Proses Penilaian
                            </button>
                        
                            <button
                                type="button"
                                id="batalEditBtn" 
                                class="btn btn-secondary" 
                                style="display: none;"
                            >
                                Batal Edit
                            </button>
                        </div>
                    </div>
                    
                </form>

                {{-- Section to display processed results (initially hidden) --}}
                <div id="hasilPenilaianSection" style="display: none;" class="mt-4">
                    <div class="card">
                        <div class="card-body" id="hasilPenilaianContent">
                            {{-- Dynamic content will be loaded here --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Include penilaian.js file --}}
<script src="{{ asset('js/penilaian.js') }}"></script>
@endsection