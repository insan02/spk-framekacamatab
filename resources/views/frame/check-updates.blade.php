@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Periksa Kelengkapan untuk Frame: {{ $frame->frame_merek }}</h2>
    
    <div class="row mb-4">
        <div class="col-md-4">
            @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                     class="img-fluid" 
                     alt="{{ $frame->frame_merek }}">
            @else
                <div class="text-center p-3 border">
                    <p>Gambar tidak tersedia</p>
                </div>
            @endif
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    Informasi Frame
                </div>
                <div class="card-body">
                    <h5 class="card-title">{{ $frame->frame_merek }}</h5>
                    <p class="card-text">Harga: Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h5>Kriteria yang Perlu Dilengkapi</h5>
        </div>
        <div class="card-body">
            @if(count($missingKriterias) > 0 || count($outdatedSubkriterias) > 0)
                <div class="alert alert-info">
                    Frame ini memerlukan kelengkapan data. Silakan edit frame untuk melengkapi data.
                </div>
                
                @if(count($missingKriterias) > 0)
                <h6>Kriteria yang belum memiliki nilai:</h6>
                <ul>
                    @foreach($missingKriterias as $kriteria)
                        <li>{{ $kriteria->kriteria_nama }}</li>
                    @endforeach
                </ul>
                @endif
                
                @if(count($outdatedSubkriterias) > 0)
                <h6>Subkriteria yang perlu diperbarui:</h6>
                <ul>
                    @foreach($outdatedSubkriterias as $item)
                        <li>{{ $item['kriteria']->kriteria_nama }}: {{ $item['message'] }}</li>
                    @endforeach
                </ul>
                @endif
                
                <a href="{{ route('frame.edit', $frame->frame_id) }}" class="btn btn-primary">Edit Frame</a>
            @else
                <div class="alert alert-success">
                    Frame ini sudah memiliki nilai untuk semua kriteria dan subkriteria yang tersedia.
                </div>
            @endif
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Nilai Kriteria Saat Ini</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kriteria</th>
                            <th>Subkriteria</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $groupedSubkriterias = $frame->frameSubkriterias->groupBy('kriteria_id');
                        @endphp
                        
                        @foreach($groupedSubkriterias as $kriteriaId => $subkriterias)
                            <tr>
                                <td>{{ $subkriterias->first()->kriteria->kriteria_nama }}</td>
                                <td>
                                    @foreach($subkriterias as $nilai)
                                        @if($nilai->subkriteria)
                                            {{ $nilai->subkriteria->subkriteria_nama }}
                                        @else
                                            <span class="text-danger">Subkriteria tidak valid</span>
                                        @endif
                                        @if(!$loop->last)
                                            <br>
                                        @endif
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="{{ route('frame.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection