@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-history me-2"></i>Riwayat Penilaian
            </h4>

            @if(session('success'))
                <div data-success-message="{{ session('success') }}" style="display:none;"></div>
            @endif
            
            @if(auth()->user()->role === 'owner')
            <div>
                <button type="button" class="btn btn-sm btn-warning me-2" data-bs-toggle="modal" data-bs-target="#printReportModal">
                    <i class="fas fa-print me-1"></i>Cetak Laporan
                </button>
                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#resetDataModal">
                    <i class="fas fa-trash-alt me-1"></i>Reset Data
                </button>
            </div>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped" id="riwayatTable">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Pelanggan</th>
                            <th>No HP</th>
                            <th>Kriteria Terpilih</th>
                            <th>Rekomendasi Teratas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($histories as $history)
                        <tr>
                            <td>
                                {{ $history->created_at->setTimezone('Asia/Jakarta')->format('d M Y') }}<br>
                                {{ $history->created_at->setTimezone('Asia/Jakarta')->format('H:i') }} WIB
                            </td>                            
                            <td>{{ $history->customer_name ?? ($history->customer->name ?? 'Unknown') }}</td>
                            <td>{{ $history->customer_phone ?? ($history->customer->phone ?? 'Unknown') }}</td>
                            <td>
                                @php
                                    $kriteriaTerpilih = $history->kriteria_dipilih ?? [];
                                @endphp
                                @foreach($kriteriaTerpilih as $kriteria => $subkriteria)
                                    <small>{{ $kriteria }}: {{ $subkriteria }}<br></small>
                                @endforeach
                            </td>
                            <td>
                                @php
                                    $rekomendasi = $history->rekomendasi_data ?? [];
                                    $topRekomendasi = $rekomendasi[0] ?? null;
                                @endphp
                                @if($topRekomendasi)
                                    <div class="d-flex flex-column align-items-start">
                                       @if(isset($topRekomendasi['frame']['frame_foto']))
                                            <img src="{{ asset('storage/' . $topRekomendasi['frame']['frame_foto']) }}" 
                                                alt="{{ $topRekomendasi['frame']['frame_merek'] ?? 'Frame Image' }}" 
                                                class="img-thumbnail mb-2" 
                                                style="max-width: 200px; max-height: 100px;"
                                                onerror="this.style.display='none';">
                                        @endif
                                        <div class="small text-secondary">
                                            <div><span class="text-dark">ID:</span> {{ $topRekomendasi['frame']['frame_id'] ?? '-' }}</div>
                                            <div><span class="text-dark">Merek:</span> {{ $topRekomendasi['frame']['frame_merek'] ?? '-' }}</div>
                                            <div><span class="text-dark">Lokasi:</span> {{ $topRekomendasi['frame']['frame_lokasi'] ?? '-' }}</div>
                                            <div><span class="text-dark">Skor:</span> {{ number_format($topRekomendasi['score'] ?? 0, 4) }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">Tidak ada rekomendasi</span>
                                @endif
                            </td>                            
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('rekomendasi.show', $history->recommendation_history_id) }}" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Detail
                                    </a>
                                    
                                    <a href="{{ route('rekomendasi.print', $history->recommendation_history_id) }}" 
                                        class="btn btn-sm btn-warning print-btn"
                                        target="_blank">
                                        <i class="fas fa-print"></i> Cetak
                                     </a>
                                     
                                    @if(auth()->user()->role == 'owner')
                                    <form action="{{ route('rekomendasi.destroy', $history->recommendation_history_id) }}" 
                                          method="POST" class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger delete-btn">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        {{ $histories->links() }}
    </div>
</div>
</div>

<!-- Print Report Modal -->
<div class="modal fade" id="printReportModal" tabindex="-1" aria-labelledby="printReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="printReportModalLabel">Cetak Laporan Penilaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="printReportForm" action="{{ route('rekomendasi.print-all') }}" method="GET" target="_blank">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date">
                    </div>
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="end_date" name="end_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-warning" id="submitPrintReport">Cetak</button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Data Modal -->
<div class="modal fade" id="resetDataModal" tabindex="-1" aria-labelledby="resetDataModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="resetDataModalLabel">Reset Data Rekomendasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="resetDataForm" action="{{ route('rekomendasi.reset') }}" method="POST">
                    @csrf
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Perhatian!</strong> Tindakan ini akan menghapus semua data riwayat rekomendasi sesuai dengan rentang tanggal yang dipilih dan tidak dapat dikembalikan.
                    </div>
                    
                    <div class="mb-3">
                        <label for="reset_start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="reset_start_date" name="start_date">
                    </div>
                    <div class="mb-3">
                        <label for="reset_end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" class="form-control" id="reset_end_date" name="end_date">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="submitResetData">Reset</button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/rekomendasi.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle print individual item
    document.querySelectorAll('.print-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            window.open(url, '_blank');
        });
    });
    
    // Adding error message divs to print form
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Insert error message elements after inputs
    startDateInput.insertAdjacentHTML('afterend', '<div class="invalid-feedback" id="start_date_error"></div>');
    endDateInput.insertAdjacentHTML('afterend', '<div class="invalid-feedback" id="end_date_error"></div>');
    
    // Same for reset form
    const resetStartDateInput = document.getElementById('reset_start_date');
    const resetEndDateInput = document.getElementById('reset_end_date');
    
    resetStartDateInput.insertAdjacentHTML('afterend', '<div class="invalid-feedback" id="reset_start_date_error"></div>');
    resetEndDateInput.insertAdjacentHTML('afterend', '<div class="invalid-feedback" id="reset_end_date_error"></div>');
    
    // Reset validation state
    function resetValidation(form) {
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
    }
    
    // Handle print report form submission
    document.getElementById('submitPrintReport').addEventListener('click', function() {
        const form = document.getElementById('printReportForm');
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        let isValid = true;
        
        // Reset previous validation
        resetValidation(form);
        
        // Validate start date
        if (!startDate.value) {
            startDate.classList.add('is-invalid');
            document.getElementById('start_date_error').textContent = 'Tanggal awal harus diisi';
            isValid = false;
        }
        
        // Validate end date
        if (!endDate.value) {
            endDate.classList.add('is-invalid');
            document.getElementById('end_date_error').textContent = 'Tanggal akhir harus diisi';
            isValid = false;
        }
        
        // Validate date range
        if (startDate.value && endDate.value && new Date(startDate.value) > new Date(endDate.value)) {
            endDate.classList.add('is-invalid');
            document.getElementById('end_date_error').textContent = 'Tanggal akhir harus sama dengan atau setelah tanggal awal';
            isValid = false;
        }
        
        // Submit the form if valid
        if (isValid) {
            form.submit();
        }
    });
    
    // Handle reset data form submission
    document.getElementById('submitResetData').addEventListener('click', function() {
        const form = document.getElementById('resetDataForm');
        const startDate = document.getElementById('reset_start_date');
        const endDate = document.getElementById('reset_end_date');
        let isValid = true;
        
        // Reset previous validation
        resetValidation(form);
        
        // Validate start date
        if (!startDate.value) {
            startDate.classList.add('is-invalid');
            document.getElementById('reset_start_date_error').textContent = 'Tanggal awal harus diisi';
            isValid = false;
        }
        
        // Validate end date
        if (!endDate.value) {
            endDate.classList.add('is-invalid');
            document.getElementById('reset_end_date_error').textContent = 'Tanggal akhir harus diisi';
            isValid = false;
        }
        
        // Validate date range
        if (startDate.value && endDate.value && new Date(startDate.value) > new Date(endDate.value)) {
            endDate.classList.add('is-invalid');
            document.getElementById('reset_end_date_error').textContent = 'Tanggal akhir harus sama dengan atau setelah tanggal awal';
            isValid = false;
        }
        
        // Confirm and submit if valid
        if (isValid) {
            form.submit();
        }
    });
    
    // Show success message if any
    const successMessageElement = document.querySelector('[data-success-message]');
    if (successMessageElement) {
        const message = successMessageElement.getAttribute('data-success-message');
        if (message) {
            // Use sweet alert if available, otherwise use regular alert
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Berhasil!',
                    text: message,
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            } else {
                alert(message);
            }
        }
    }
});
</script>
@endsection