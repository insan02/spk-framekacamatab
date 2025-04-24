@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        {{-- Tombol Kembali --}}
        <div class="mb-3">
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-edit"></i> Edit Karyawan
                </h4>
            </div>

            <div class="card-body">
                {{-- Pesan Sukses --}}
                @if(session('success'))
                    <div data-success-message="{{ session('success') }}" style="display:none;"></div>
                @endif

                {{-- Pesan Error --}}
                @if(session('error'))
                    <div data-error-message="{{ session('error') }}" style="display:none;"></div>
                @endif

                <form method="POST" action="{{ route('employees.update', $employee) }}" id="form-edit">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="name" class="form-label">Nama</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $employee->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mt-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email', $employee->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Perbarui Karyawan
                        </button>
                        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Batal</a>
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
