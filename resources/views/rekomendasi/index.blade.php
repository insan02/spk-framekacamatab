@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
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

<script src="{{ asset('js/rekomendasi.js') }}"></script>
@endsection