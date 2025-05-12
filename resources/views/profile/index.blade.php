@extends('layouts.app')
@section('content')
<div class="container-fluid">

    @if(session('success'))
        <div data-success-message="{{ session('success') }}" style="display:none;"></div>
    @endif
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-user me-2"></i>Profil Saya
            </h4>
        </div>

        
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-3 text-center mb-4 mb-md-0">
                    <div class="avatar-wrapper mb-3">
                        <div class="avatar-placeholder bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px;">
                            <i class="fas fa-user text-primary" style="font-size: 64px;"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        @if(auth()->user()->role === 'owner')
                            <a href="{{ route('profile.edit') }}" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-edit me-2"></i>Edit Profil
                            </a>
                        @endif
                        <a href="{{ route('password.edit') }}" class="btn btn-outline-primary w-100">
                            <i class="fas fa-key me-2"></i>Ubah Password
                        </a>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-info-circle me-2"></i>Informasi Profil
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label class="text-muted d-block mb-1">Nama Lengkap</label>
                                        <h6 class="mb-0 pb-2 border-bottom">{{ $user->name }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label class="text-muted d-block mb-1">Email</label>
                                        <h6 class="mb-0 pb-2 border-bottom">{{ $user->email }}</h6>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="profile-item">
                                        <label class="text-muted d-block mb-1">Role</label>
                                        <h6 class="mb-0 pb-2 border-bottom text-capitalize">{{ auth()->user()->role }}</h6>
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