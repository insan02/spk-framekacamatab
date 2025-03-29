@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-glasses"></i> Detail Frame
                </h4>
                <a href="{{ route('frame.index') }}" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Left Column: Frame Details -->
                    <div class="col-md-6">
                        <!-- Merek Frame Card -->
                        <div class="card mb-3">
                            <div class="card-header">
                                Merek Frame
                            </div>
                            <div class="card-body">
                                <p class="form-control-plaintext">{{ $frame->frame_merek }}</p>
                            </div>
                        </div>
                        
                        <!-- Harga Frame Card -->
                        <div class="card mb-3">
                            <div class="card-header">
                                Harga Frame
                            </div>
                            <div class="card-body">
                                <p class="form-control-plaintext">Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <!-- Foto Frame Card -->
                        <div class="card mb-3">
                            <div class="card-header">
                                Foto Frame
                            </div>
                            <div class="card-body">
                                @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                                    <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                         alt="{{ $frame->frame_merek }}" 
                                         class="img-thumbnail" 
                                         style="max-height: 200px;">
                                @else
                                    <p class="text-muted">Tidak ada foto</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Kriteria -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Nilai Kriteria</h5>
                            </div>
                            <div class="card-body">
                                @php
                                    $groupedSubkriterias = $frame->frameSubkriterias->groupBy('kriteria_id');
                                @endphp
                                
                                @foreach($kriterias as $kriteria)
                                    <div class="mb-4">
                                        <h6 class="font-weight-bold">{{ $kriteria->kriteria_nama }}</h6>
                                        
                                        @if(isset($groupedSubkriterias[$kriteria->kriteria_id]))
                                            <ul class="list-group">
                                                @foreach($groupedSubkriterias[$kriteria->kriteria_id] as $frameSubkriteria)
                                                    <li class="list-group-item">
                                                        @if($frameSubkriteria->subkriteria)
                                                            {{ $frameSubkriteria->subkriteria->subkriteria_nama }}
                                                        @else
                                                            <span class="text-danger">Subkriteria tidak valid</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-muted">Tidak ada nilai untuk kriteria ini</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection