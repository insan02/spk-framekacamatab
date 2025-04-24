@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-history me-2"></i>Riwayat Rekomendasi
            </h4>
            <div>
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#printReportModal">
                    <i class="fas fa-print me-1"></i>Cetak Laporan
                </button>
            </div>
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
                            <td>{{ $history->created_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
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
                                            <img src="{{ asset('storage/'.$topRekomendasi['frame']['frame_foto']) }}" 
                                                 alt="{{ $topRekomendasi['frame']['frame_merek'] }}" 
                                                 class="img-thumbnail mb-2" 
                                                 style="max-width: 200px; max-height: 100px;">
                                        @endif
                                        <div class="small text-secondary">
                                            <div><span class="text-dark">Merek:</span> {{ $topRekomendasi['frame']['frame_merek'] ?? '-' }}</div>
                                            <div><span class="text-dark">Lokasi:</span> {{ $topRekomendasi['frame']['frame_lokasi'] ?? '-' }}</div>
                                            <div><span class="text-dark">Harga:</span> 
                                                Rp {{ isset($topRekomendasi['frame']['frame_harga']) ? number_format($topRekomendasi['frame']['frame_harga'], 0, ',', '.') : '-' }}
                                            </div>
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
                                        class="btn btn-sm btn-secondary print-btn">
                                         <i class="fas fa-print"></i> Cetak
                                     </a>
                                    @if(auth()->user()->role !== 'owner')
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
            <div class="modal-header">
                <h5 class="modal-title" id="printReportModalLabel">Cetak Laporan Rekomendasi</h5>
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
                <button type="button" class="btn btn-primary" id="submitPrintReport">Cetak</button>
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
    
    // Handle print report form submission
    document.getElementById('submitPrintReport').addEventListener('click', function() {
        // Validate dates
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        if (endDate && startDate && new Date(endDate) < new Date(startDate)) {
            alert('Tanggal akhir tidak boleh sebelum tanggal mulai');
            return false;
        }
        
        // Submit the form
        document.getElementById('printReportForm').submit();
    });
});
</script>
@endsection