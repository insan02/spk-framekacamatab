@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Tambah Kriteria</h2>

    <form action="{{ route('kriteria.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="kriteria_nama" class="form-label">Nama Kriteria</label>
            <input type="text" class="form-control @error('kriteria_nama') is-invalid @enderror" id="kriteria_nama" name="kriteria_nama" value="{{ old('kriteria_nama') }}" required>
            @error('kriteria_nama')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>
@endsection
