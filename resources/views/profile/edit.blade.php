@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-key me-2"></i>Edit Password
            </h4>
        </div>

        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Password Lama --}}
                <div class="mb-3">
                    <label for="current_password" class="form-label">Password Lama</label>
                    <div class="input-group">
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Password Baru --}}
                <div class="mb-3">
                    <label for="new_password" class="form-label">Password Baru</label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                    @error('new_password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Konfirmasi Password --}}
                <div class="mb-3">
                    <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password_confirmation">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Perbarui Password</button>
                <a href="{{ route('profile') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
