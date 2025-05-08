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

                        <!-- Lokasi Frame Card -->
                        <div class="card mb-3">
                            <div class="card-header">
                                Lokasi Frame
                            </div>
                            <div class="card-body">
                                <p class="form-control-plaintext">{{ $frame->frame_lokasi }}</p>
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
                                        
                                        @if(isset($groupedSubkriterias[$kriteria->kriteria_id]) && $groupedSubkriterias[$kriteria->kriteria_id]->count() > 0)
                                            @php
                                                $frameSubkriterias = $groupedSubkriterias[$kriteria->kriteria_id];
                                                $hasManualValue = false;
                                                $manualValues = [];
                                                $checkboxValues = [];
                                                
                                                foreach($frameSubkriterias as $fs) {
                                                    if($fs->subkriteria) {
                                                        if($fs->manual_value !== null) {
                                                            // This is a manual value
                                                            $hasManualValue = true;
                                                            $manualValues[] = [
                                                                'value' => $fs->manual_value,
                                                                'name' => $fs->subkriteria->subkriteria_nama
                                                            ];
                                                        } else {
                                                            // This is a checkbox value
                                                            $checkboxValues[] = $fs->subkriteria->subkriteria_nama;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            
                                            <ul class="list-group">
                                                @if(count($manualValues) > 0)
                                                    @foreach($manualValues as $manualItem)
                                                        <li class="list-group-item">
                                                            {{ number_format($manualItem['value'], 2, ',', '.') }} ({{ $manualItem['name'] }})
                                                        </li>
                                                    @endforeach
                                                @endif
                                                
                                                @if(count($checkboxValues) > 0)
                                                    <li class="list-group-item">
                                                        {{ implode(', ', $checkboxValues) }}
                                                    </li>
                                                @endif
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