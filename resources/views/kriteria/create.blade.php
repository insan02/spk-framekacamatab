@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="mb-3">
            <a href="{{ route('kriteria.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i></i>Tambah Kriteria
                </h4>
            </div>
            <div class="card-body">

                <form action="{{ route('kriteria.store') }}" method="POST">
                    @csrf
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
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
