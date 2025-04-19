<!-- resources/views/logs/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-3">
        <a href="{{ route('logs.index') }}" class="btn btn-secondary">
            &laquo; Kembali ke Daftar Log
        </a>
    </div>
    
    <div class="container-fluid">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Detail Log
                </h4>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Waktu</div>
                    <div class="col-md-9">{{ $log->created_at->format('d-m-Y H:i:s') }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Karyawan</div>
                    <div class="col-md-9">{{ $log->user_name }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Modul</div>
                    <div class="col-md-9">{{ ucfirst($log->module) }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Aksi</div>
                    <div class="col-md-9">
                        @if($log->action == 'create')
                            <span class="badge bg-success">Tambah</span>
                        @elseif($log->action == 'update')
                            <span class="badge bg-primary">Edit</span>
                        @elseif($log->action == 'delete')
                            <span class="badge bg-danger">Hapus</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($log->action) }}</span>
                        @endif
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">ID Referensi</div>
                    <div class="col-md-9">{{ $log->reference_id }}</div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Deskripsi</div>
                    <div class="col-md-9">{{ $log->description }}</div>
                </div>
                
                @if($log->old_values)
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Nilai Lama</div>
                    <div class="col-md-9">
                        <pre class="bg-light p-3 rounded">{{ json_encode(json_decode($log->old_values), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                @endif
                
                @if($log->new_values)
                <div class="row mb-3">
                    <div class="col-md-3 fw-bold">Nilai Baru</div>
                    <div class="col-md-9">
                        <pre class="bg-light p-3 rounded">{{ json_encode(json_decode($log->new_values), JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection