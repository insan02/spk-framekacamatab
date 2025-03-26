@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Detail Frame</h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="font-weight-bold">Merek Frame:</label>
                        <p>{{ $frame->frame_merek }}</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Harga Frame:</label>
                        <p>Rp {{ number_format($frame->frame_harga, 0, ',', '.') }}</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Foto Frame:</label>
                        <div>
                            @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                                <img src="{{ asset('storage/' . $frame->frame_foto) }}" alt="{{ $frame->frame_merek }}" class="img-thumbnail" style="max-height: 300px;">
                            @else
                                <p class="text-muted">Tidak ada foto</p>
                            @endif
                        </div>
                    </div>
                </div>
                
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
    
    <div class="d-flex">
        {{-- <a href="{{ route('frame.edit', $frame->frame_id) }}" class="btn btn-warning mr-2">Edit</a>
        <form action="{{ route('frame.destroy', $frame->frame_id) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger mr-2" onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
        </form> --}}
        <a href="{{ route('frame.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</div>
@endsection