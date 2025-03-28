@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-history me-2"></i>Riwayat Rekomendasi
            </h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped" id="riwayatTable">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Pelanggan</th>
                            <th>Kriteria Terpilih</th>
                            <th>Rekomendasi Teratas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($histories as $history)
                        <tr>
                            <td>{{ $history->created_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</td>
                            <td>{{ $history->nama_pelanggan }}</td>
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
                                    {{ $topRekomendasi['frame']['frame_merek'] }} 
                                    (Skor: {{ number_format($topRekomendasi['score'], 4) }})
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('rekomendasi.show', $history->recommendation_history_id) }}" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> Detail
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

@push('scripts')
<script>
$(document).ready(function() {
    // DataTables initialization
    $('#riwayatTable').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "order": [[0, 'asc']],
        "language": {
            "search": "Cari:",
            "paginate": {
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            },
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Tidak ada data yang ditampilkan",
            "zeroRecords": "Tidak ditemukan data yang cocok"
        }
    });

    // Delete confirmation
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Anda yakin?',
            text: "Data riwayat rekomendasi akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });
});
</script>
@endpush
@endsection