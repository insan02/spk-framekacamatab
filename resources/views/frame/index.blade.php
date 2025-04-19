<!-- Tambahan pada file index.blade.php untuk pencarian gambar -->
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        @if(session('success'))
        <div data-success-message="{{ session('success') }}" style="display: none;"></div>
        @endif
        
        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-glasses"></i> Data Frame
                </h4>
            </div>
            <div class="card-body">

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
                
                {{-- Pesan jika pencarian gambar tidak menemukan kecocokan --}}
                @if(isset($noImageMatch) && $noImageMatch)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Tidak ditemukan frame yang cocok dengan gambar yang diunggah
                </div>
                @endif

                <div class="d-flex justify-content-between mb-3">
                    @if(auth()->user()->role !== 'owner')
                    <div>
                        <a href="{{ route('frame.create') }}" class="btn btn-primary me-2">Tambah Frame</a>
                        <form action="{{ route('frame.reset-kriteria') }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Reset Kriteria Frame</button>
                        </form>
                    </div>
                    @endif
                </div>
                
                <!-- Form Pencarian dengan Text dan Gambar -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <!-- Pencarian Teks -->
                            <div class="col-md-6">
                                <form action="{{ route('frame.index') }}" method="GET" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan merek atau lokasi" value="{{ request('search') }}">
                                        <button class="btn btn-outline-primary" type="submit">
                                            <i class="fas fa-search"></i> Cari
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Di bagian pencarian gambar -->
                            {{-- <div class="col-md-6">
                                <form action="{{ route('frame.searchByImage') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="input-group mb-3">
                                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png">
                                        <button class="btn btn-primary" type="submit">Cari dengan Gambar</button>
                                    </div>
                                </form>
                            </div> --}}
                        </div>
                    </div>
                </div>

                <div id="searchResultsContainer" class="mt-4" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-search"></i> Hasil Pencarian Berdasarkan Gambar</h5>
                        </div>
                        <div class="card-body">
                            <div id="searchResults" class="row"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="frameTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Harga</th>
                                        <th>Lokasi</th>
                                        <th>Kriteria</th>
                                        <th>Status Kriteria</th>
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
                                                        style="max-width: 180px; max-height: 90px;">
                                                @else
                                                    <span class="text-muted">Tidak ada gambar</span>
                                                @endif
                                            </td>
                                            <td>{{ $frame->frame_merek }}</td>
                                            <td>Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</td>
                                            <td>{{ $frame->frame_lokasi }}</td>
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
                                                    <a href="{{ route('frame.edit', $frame->frame_id) }}" class="btn btn-sm btn-warning">Edit</a>
                                                    <form action="{{ route('frame.destroy', $frame->frame_id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                                    </form>
                                                </div>
                                            </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada data frame</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    {{-- Pagination styling --}}
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
        </div>
    </div>
</div>
@endsection
