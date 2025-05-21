@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-edit"></i> Edit Kriteria
                </h4>
            </div>
            <div class="card-body">
                {{-- Menampilkan pesan sukses dari session --}}
                @if(session('success'))
                    <div data-success-message="{{ session('success') }}" style="display:none;"></div>
                @endif

                {{-- Menampilkan pesan error dari session --}}
                @if(session('error'))
                    <div data-error-message="{{ session('error') }}" style="display:none;"></div>
                @endif

                <form action="{{ route('kriteria.update', $kriteria->kriteria_id) }}" method="POST" id="form-edit">
                    @csrf
                    @method('PUT')
                
                    <div class="form-group mb-3">
                        <label for="kriteria_id" class="form-label">ID Kriteria</label>
                        <input type="text" class="form-control @error('kriteria_id') is-invalid @enderror" 
                               id="kriteria_id" name="kriteria_id" value="{{ old('kriteria_id', $kriteria->kriteria_id) }}" 
                               pattern="C\d{2}" title="Format harus C diikuti 2 digit angka (contoh: C01)" required>
                        <small class="form-text text-muted">Format ID: C diikuti 2 digit angka (contoh: C01)</small>
                        @error('kriteria_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                
                    <div class="form-group mb-3">
                        <label for="kriteria_nama" class="form-label">Nama Kriteria</label>
                        <input type="text" class="form-control @error('kriteria_nama') is-invalid @enderror" 
                               id="kriteria_nama" name="kriteria_nama" value="{{ old('kriteria_nama', $kriteria->kriteria_nama) }}" 
                               pattern="[A-Za-z\s]+" title="Hanya huruf yang diperbolehkan" required>
                        @error('kriteria_nama')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                
                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Perbarui Kriteria
                        </button>
                        
                        <a href="{{ route('kriteria.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    @if($errors->any())
    @foreach($errors->all() as $error)
        <div class="invalid-feedback" style="display:none;">
            <strong>{{ $error }}</strong>
        </div>
    @endforeach
    @endif

    @if(session('success'))
        <div data-success-message="{{ session('success') }}" style="display:none;"></div>
    @endif
</div>
@endsection