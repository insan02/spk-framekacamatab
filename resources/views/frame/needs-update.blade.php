@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-glasses"></i> Frame yang Perlu Dilengkapi
                </h4>
            </div>
            
            <div class="card-body">
                @if($totalFramesNeedingUpdate === 0)
                <div class="alert alert-success">
                    Semua frame sudah lengkap. Tidak ada frame yang perlu diperbarui.
                </div>
                @else
                <div class="alert alert-warning">
                    Terdapat <strong>{{ $totalFramesNeedingUpdate }} frame</strong> yang perlu dilengkapi.
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <div></div>
                    <div>
                        <span class="text-muted">
                            Menampilkan {{ $framesNeedingUpdate->firstItem() ?? 0 }} - 
                            {{ $framesNeedingUpdate->lastItem() ?? 0 }} 
                            dari {{ $totalFramesNeedingUpdate }} data
                        </span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Frame</th>
                                        <th>Kriteria Invalid</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($framesNeedingUpdate as $index => $frame)
                                    <tr>
                                        <td>{{ $framesNeedingUpdate->firstItem() + $index }}</td>
                                        <td>{{ $frame->frame_merek ?? 'Nama Tidak Tersedia' }}</td>
                                        <td>
                                            @php
                                                $missingKriterias = $allKriterias
                                                    ->whereNotIn('kriteria_id', optional($frame->frameSubkriterias)->pluck('kriteria_id') ?? collect())
                                                    ->pluck('kriteria_nama');
                                            @endphp
                                            {{ $missingKriterias->isEmpty() ? 'Tidak ada' : $missingKriterias->join(', ') }}
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('frame.edit', $frame->frame_id) }}" 
                                                class="btn btn-sm btn-warning">
                                                    Lengkapi
                                                </a>
                                                {{-- <a href="{{ route('frame.checkUpdates', $frame->frame_id) }}" 
                                                class="btn btn-sm btn-info">
                                                    Detail
                                                </a> --}}
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada frame yang perlu dilengkapi</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    @if ($framesNeedingUpdate->hasPages())
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                {{-- Previous Page Link --}}
                                @if ($framesNeedingUpdate->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link">«</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $framesNeedingUpdate->previousPageUrl() }}" rel="prev" aria-label="Previous">«</a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($framesNeedingUpdate->getUrlRange(max(1, $framesNeedingUpdate->currentPage() - 2), min($framesNeedingUpdate->lastPage(), $framesNeedingUpdate->currentPage() + 2)) as $page => $url)
                                    @if ($page == $framesNeedingUpdate->currentPage())
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
                                @if ($framesNeedingUpdate->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $framesNeedingUpdate->nextPageUrl() }}" rel="next" aria-label="Next">»</a>
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
        @endif
    </div>
</div>
@endsection