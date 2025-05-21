@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Kriteria
                </h4>
            </div>
            <div class="card-body">

                <form action="{{ route('kriteria.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="kriteria_id" class="form-label">ID Kriteria</label>
                        <input type="text" class="form-control @error('kriteria_id') is-invalid @enderror" 
                               id="kriteria_id" name="kriteria_id" value="{{ old('kriteria_id', $newId) }}" 
                               pattern="C\d{2}" title="Format harus C diikuti 2 digit angka (contoh: C01)" required>
                        <small class="form-text text-muted">Format ID: C diikuti 2 digit angka (contoh: C01)</small>
                        @error('kriteria_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="kriteria_nama" class="form-label">Nama Kriteria</label>
                        <input type="text" class="form-control @error('kriteria_nama') is-invalid @enderror" 
                               id="kriteria_nama" name="kriteria_nama" value="{{ old('kriteria_nama') }}" 
                               pattern="[A-Za-z\s]+" title="Hanya huruf yang diperbolehkan" required>
                        @error('kriteria_nama')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        
                        <a href="{{ route('kriteria.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    
                </form>
            </div>
        </div>
    </div>
</div>
@endsection