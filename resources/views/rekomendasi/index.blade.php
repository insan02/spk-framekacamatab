@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Riwayat Rekomendasi Frame Kacamata</h2>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Pelanggan</th>
                    <th>Kriteria Terpilih</th>
                    <th>3 Rekomendasi Terbaik</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasis as $penilaian)
                <tr>
                    <td>{{ $penilaian->tgl_penilaian->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</td>
                    <td>{{ $penilaian->nama_pelanggan }}</td>
                    <td>
                        <ul class="list-unstyled mb-0">
                            @foreach($penilaian->detailPenilaians as $detail)
                            <li>
                                <strong>{{ $detail->kriteria->kriteria_nama }}:</strong> 
                                {{ $detail->subkriteria->subkriteria_nama }}
                            </li>
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        @if($penilaian->rekomendasis->isNotEmpty())
                            <ol class="pl-3 mb-0">
                                @foreach($penilaian->rekomendasis->sortByDesc('nilai_akhir')->take(3) as $index => $rekomendasi)
                                    <li>
                                        <strong>{{ $rekomendasi->frame->frame_merek }}</strong> 
                                        <small>({{ number_format($rekomendasi->nilai_akhir, 4) }})</small>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <div class="btn-group" role="group">
                            <a href="{{ route('rekomendasi.show', $penilaian->penilaian_id) }}" 
                               class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                            <a href="{{ route('rekomendasi.print', $penilaian->penilaian_id) }}" 
                               class="btn btn-success btn-sm">
                                <i class="fas fa-print"></i> Cetak
                            </a>
                            <form action="{{ route('rekomendasi.destroy', $penilaian->penilaian_id) }}" 
                                  method="POST" class="d-inline" 
                                  onsubmit="return confirm('Hapus riwayat rekomendasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash-alt"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection