@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-search"></i> Hasil Pencarian Berdasarkan Gambar
                </h4>
                <a href="{{ route('frame.index') }}" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Ditemukan {{ count($frames) }} frame yang mirip dengan gambar yang Anda unggah.
                </div>
                
                <div class="row">
                    @foreach($frames as $frame)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-light text-center">
                                <h5 class="mb-0">{{ $frame->frame_merek }}</h5>
                            </div>
                            <div class="card-body text-center">
                                @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                                    <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                        alt="{{ $frame->frame_merek }}" 
                                        class="img-fluid mb-3" 
                                        style="max-height: 200px;">
                                @else
                                    <div class="text-muted p-5">Tidak ada gambar</div>
                                @endif
                                
                                <p><strong>Harga:</strong> Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</p>
                                <p><strong>Lokasi:</strong> {{ $frame->frame_lokasi }}</p>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <a href="{{ route('frame.show', $frame->frame_id) }}" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Lihat Detail
                                    </a>
                                    @if(auth()->user()->role !== 'owner')
                                    <a href="{{ route('frame.edit', $frame->frame_id) }}" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if(count($frames) === 0)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Tidak ditemukan frame yang cocok dengan gambar yang diunggah.
                </div>
                @endif
                
                <div class="text-center mt-4">
                    <a href="{{ route('frame.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Frame
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection