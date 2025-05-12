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

            @if(session('success'))
                <div data-success-message="{{ session('success') }}" style="display:none;"></div>
            @endif

            @if(session('error'))
                <div data-error-message="{{ session('error') }}" style="display:none;"></div>
            @endif
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
                        
                        <div class="col-md-12 d-flex justify-content-between align-items-center">
                            <div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ route('logs.index') }}" class="btn btn-secondary">Reset</a>
                            </div>
                            @if(auth()->user()->role === 'owner')
                            <div>
                                <button type="button" id="resetLogsBtn" class="btn btn-danger">
                                    <i class="fas fa-trash me-1"></i> Reset Log
                                </button>
                            </div>
                            @endif
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
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye me-1"></i>Detail
                                            </a>
                                            @if(auth()->user()->role === 'owner')
                                                <form action="{{ route('logs.destroy', $log->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                                </form>
                                            @endif
                                        </div>
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
                    
                    <!-- Pagination Links -->
                    <div class="d-flex justify-content-end mt-4">
                        {{ $logs->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Logs By Date Range Modal -->
<div class="modal fade" id="resetLogsModal" tabindex="-1" aria-labelledby="resetLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-dark">
                <h5 class="modal-title text-center w-100" id="resetLogsModalLabel">Reset Data Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resetLogForm" action="{{ route('logs.deleteAll') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    
                    <div class="alert alert-danger">
                        <p class="fw-bold mb-2">PERHATIAN!</p>
                        <p>Anda akan menghapus data log aktivitas sistem berdasarkan rentang tanggal.</p>
                        <p>Data yang sudah dihapus tidak dapat dikembalikan!</p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_date_from" class="form-label required">Dari Tanggal</label>
                        <input type="date" class="form-control" id="modal_date_from" name="date_from" required>
                        <div class="invalid-feedback" id="date_from_error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_date_to" class="form-label required">Sampai Tanggal</label>
                        <input type="date" class="form-control" id="modal_date_to" name="date_to" required>
                        <div class="invalid-feedback" id="date_to_error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmResetLogs">Reset</button>
            </div>
        </div>
    </div>
</div>


<script src="{{ asset('js/logs.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reset logs button
    const resetLogsBtn = document.getElementById('resetLogsBtn');
    const confirmResetLogsBtn = document.getElementById('confirmResetLogs');
    const resetLogsModal = new bootstrap.Modal(document.getElementById('resetLogsModal'));
    const resetLogForm = document.getElementById('resetLogForm');
    
    // Copy date range from main form if available
    resetLogsBtn.addEventListener('click', function() {
        const mainDateFrom = document.getElementById('date_from');
        const mainDateTo = document.getElementById('date_to');
        const modalDateFrom = document.getElementById('modal_date_from');
        const modalDateTo = document.getElementById('modal_date_to');
        
        // Set today's date as default if no date is selected
        const today = new Date().toISOString().split('T')[0];
        
        modalDateFrom.value = mainDateFrom.value || today;
        modalDateTo.value = mainDateTo.value || today;
        
        resetLogsModal.show();
    });
    
    // Form validation and submission
    confirmResetLogsBtn.addEventListener('click', function() {
        const dateFrom = document.getElementById('modal_date_from');
        const dateTo = document.getElementById('modal_date_to');
        let isValid = true;
        
        // Reset error messages
        document.getElementById('date_from_error').textContent = '';
        document.getElementById('date_to_error').textContent = '';
        dateFrom.classList.remove('is-invalid');
        dateTo.classList.remove('is-invalid');
        
        // Validate date from
        if (!dateFrom.value) {
            dateFrom.classList.add('is-invalid');
            document.getElementById('date_from_error').textContent = 'Tanggal awal harus diisi';
            isValid = false;
        }
        
        // Validate date to
        if (!dateTo.value) {
            dateTo.classList.add('is-invalid');
            document.getElementById('date_to_error').textContent = 'Tanggal akhir harus diisi';
            isValid = false;
        }
        
        // Validate date range
        if (dateFrom.value && dateTo.value && new Date(dateFrom.value) > new Date(dateTo.value)) {
            dateTo.classList.add('is-invalid');
            document.getElementById('date_to_error').textContent = 'Tanggal akhir harus sama dengan atau setelah tanggal awal';
            isValid = false;
        }
        
        // Submit if valid
        if (isValid) {
            resetLogForm.submit();
        }
    });
    
    // Display success/error messages if present
    const successMessage = document.querySelector('[data-success-message]');
    const errorMessage = document.querySelector('[data-error-message]');
    
    if (successMessage) {
        Swal.fire({
            title: 'Berhasil!',
            text: successMessage.dataset.successMessage,
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }
    
    if (errorMessage) {
        Swal.fire({
            title: 'Error!',
            text: errorMessage.dataset.errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
});
</script>
@endsection