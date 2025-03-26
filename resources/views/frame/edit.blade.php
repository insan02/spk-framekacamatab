@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Edit Frame</h2>
    
    <form action="{{ route('frame.update', $frame->frame_id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="frame_merek">Merek Frame</label>
                    <input type="text" class="form-control @error('frame_merek') is-invalid @enderror" 
                           id="frame_merek" name="frame_merek" 
                           value="{{ old('frame_merek', $frame->frame_merek) }}" required>
                    @error('frame_merek')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="frame_harga">Harga Frame</label>
                    <input type="number" class="form-control @error('frame_harga') is-invalid @enderror" 
                           id="frame_harga" name="frame_harga" 
                           value="{{ old('frame_harga', $frame->frame_harga) }}" required>
                    @error('frame_harga')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="frame_foto">Foto Frame</label>
                    <input type="file" class="form-control-file @error('frame_foto') is-invalid @enderror" 
                           id="frame_foto" name="frame_foto">
                    @error('frame_foto')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    
                    @if($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $frame->frame_foto) }}" 
                                 alt="{{ $frame->frame_merek }}" 
                                 class="img-thumbnail" 
                                 style="max-height: 200px;">
                            <p class="small text-muted">Foto saat ini. Unggah foto baru untuk menggantinya.</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="col-md-6">
                @foreach($kriterias as $kriteria)
                    @php
                        $isPriceKriteria = Str::contains(strtolower($kriteria->kriteria_nama), 'harga');
                        $selectedValues = $frame->frameSubkriterias
                            ->where('kriteria_id', $kriteria->kriteria_id)
                            ->pluck('subkriteria_id')
                            ->toArray();
                    @endphp
                    
                    @if(!$isPriceKriteria)
                    <div class="card mb-3">
                        <div class="card-header">
                            {{ $kriteria->kriteria_nama }}
                            @if(isset($missingKriterias) && in_array($kriteria->kriteria_id, $missingKriterias))
                                <span class="badge badge-warning">Baru</span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($kriteria->subkriterias->count() > 0)
                                @foreach($kriteria->subkriterias as $subkriteria)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" 
                                               name="nilai[{{ $kriteria->kriteria_id }}][]" 
                                               value="{{ $subkriteria->subkriteria_id }}" 
                                               id="subkriteria{{ $subkriteria->subkriteria_id }}"
                                               {{ in_array($subkriteria->subkriteria_id, $selectedValues) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="subkriteria{{ $subkriteria->subkriteria_id }}">
                                            {{ $subkriteria->subkriteria_nama }}
                                        </label>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">Belum ada subkriteria</p>
                            @endif
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="{{ route('frame.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection