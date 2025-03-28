@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Data Frame</h2>

    {{-- Notifikasi frame yang perlu dilengkapi --}}
    @if($totalNeedsUpdate > 0)
    <div class="alert alert-warning alert-dismissible fade show">
        Terdapat <strong>{{ $totalNeedsUpdate }} frame</strong> yang perlu dilengkapi. 
        <a href="{{ route('frame.needsUpdate') }}" class="alert-link">Lihat Daftar</a>
    </div>
    @endif
    
    @if(Session::has('update_needed') && Session::get('update_needed'))
    <div class="alert alert-warning">
        <strong>Perhatian!</strong> {{ Session::get('update_message') }}
    </div>
    @endif

    <div class="d-flex justify-content-between mb-3">
        @if(auth()->user()->role !== 'owner')
        <div>
            <a href="{{ route('frame.create') }}" class="btn btn-primary me-2">Tambah Frame</a>
            <form action="{{ route('frame.reset-kriteria') }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mereset kriteria SEMUA frame? Data frame akan tetap tersimpan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-warning">Reset Kriteria Frame</button>
            </form>
        </div>
        @endif
        <div>
            <span class="text-muted">Menampilkan {{ $frames->firstItem() ?? 0 }} - {{ $frames->lastItem() ?? 0 }} dari {{ $frames->total() ?? 0 }} data</span>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>No</th>
                            <th>Foto</th>
                            <th>Merek</th>
                            <th>Harga</th>
                            <th>Kriteria</th>
                            <th>Status</th>
                            @if(auth()->user()->role !== 'owner')
                            <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($frames as $index => $frame)
                            @php
                                $needsUpdate = isset($frameNeedsUpdate[$frame->frame_id]);
                                $groupedSubkriterias = $frame->frameSubkriterias->groupBy('kriteria_id');
                            @endphp
                            <tr @if($needsUpdate) class="table-warning" @endif>
                                <td>{{ $frames->firstItem() + $index }}</td>
                                <td class="text-center">
                                    @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                                        <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                            alt="{{ $frame->frame_merek }}" 
                                            class="img-thumbnail" 
                                            style="max-width: 100px; max-height: 80px;">
                                    @else
                                        <span class="text-muted">Tidak ada gambar</span>
                                    @endif
                                </td>
                                <td>{{ $frame->frame_merek }}</td>
                                <td>Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</td>
                                <td>
                                    <a href="{{ route('frame.show', $frame->frame_id) }}" class="btn btn-sm btn-info">
                                        Lihat Detail
                                    </a>
                                </td>
                                <td>
                                    @if($needsUpdate)
                                        <span class="badge bg-warning">Perlu dilengkapi</span>
                                    @else
                                        <span class="badge bg-success">Lengkap</span>
                                    @endif
                                </td>  
                                @if(auth()->user()->role !== 'owner')                                                             
                                <td>
                                    <div class="btn-group" role="group">
                                        {{-- @if($needsUpdate)
                                            <a href="{{ route('frame.checkUpdates', $frame->frame_id) }}" class="btn btn-sm btn-info">Lengkapi</a>
                                        @endif --}}
                                        <a href="{{ route('frame.edit', $frame->frame_id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form action="{{ route('frame.destroy', $frame->frame_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data frame</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        {{-- Improved pagination styling --}}
        @if ($frames->hasPages())
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    {{-- Previous Page Link --}}
                    @if ($frames->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link">«</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $frames->previousPageUrl() }}" rel="prev" aria-label="Previous">«</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($frames->getUrlRange(max(1, $frames->currentPage() - 2), min($frames->lastPage(), $frames->currentPage() + 2)) as $page => $url)
                        @if ($page == $frames->currentPage())
                            <li class="page-item active">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($frames->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $frames->nextPageUrl() }}" rel="next" aria-label="Next">»</a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link">»</span>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
</div>
@endsection