@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>Log Aktivitas Sistem
                </h4>
            </div>
    
            <div class="card">
                <div class="card-header">Filter</div>
                <div class="card-body">
                    <form action="{{ route('logs.index') }}" method="GET" class="row">
                        <div class="col-md-4 mb-3">
                            <label for="user_id">Karyawan</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="">-- Semua Karyawan --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->user_id }}" {{ request('user_id') == $user->user_id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="module">Modul</label>
                            <select name="module" id="module" class="form-control">
                                <option value="">-- Semua Modul --</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                        {{ ucfirst($module) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="action">Aksi</label>
                            <select name="action" id="action" class="form-control">
                                <option value="">-- Semua Aksi --</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                        {{ ucfirst($action) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="date_from">Dari Tanggal</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="date_to">Sampai Tanggal</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('logs.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Log Aktivitas</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="logTable">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Karyawan</th>
                                    <th>Modul</th>
                                    <th>Aksi</th>
                                    <th>Deskripsi</th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td>{{ $log->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s') }}</td>
                                    <td>{{ $log->user_name }}</td>
                                    <td>{{ ucfirst($log->module) }}</td>
                                    <td>
                                        @if($log->action == 'create')
                                            <span class="badge bg-success">Tambah</span>
                                        @elseif($log->action == 'update')
                                            <span class="badge bg-primary">Edit</span>
                                        @elseif($log->action == 'delete')
                                            <span class="badge bg-danger">Hapus</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($log->action) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->description }}</td>
                                    <td>
                                        <a href="{{ route('logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada data log aktivitas</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="{{ asset('js/logs.js') }}"></script>
@endsection