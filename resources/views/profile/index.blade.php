@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-user me-2"></i>Profil Saya
            </h4>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Nama:</strong> {{ $user->name }}</p>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                </div>
            </div>
            <div class="d-flex gap-2">
                @if(auth()->user()->role === 'owner')
                <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profil</a>
                @endif
                <a href="{{ route('password.edit') }}" class="btn btn-primary">Edit Password</a>
            </div>
        </div>
    </div>
</div>
@endsection