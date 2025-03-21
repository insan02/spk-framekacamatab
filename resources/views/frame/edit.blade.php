@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Edit Frame</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('frame.update', $frame->frame_id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group mb-3">
                            <label for="frame_merek">Merek Frame</label>
                            <input type="text" name="frame_merek" id="frame_merek" class="form-control @error('frame_merek') is-invalid @enderror" 
                                   value="{{ old('frame_merek', $frame->frame_merek) }}" required>
                            @error('frame_merek')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="frame_foto">Foto Frame</label>
                            @if($frame->frame_foto)
                                <div class="mb-2">
                                    <img src="{{ asset('storage/'.$frame->frame_foto) }}" alt="{{ $frame->frame_merek }}" class="img-thumbnail" style="max-height: 200px;">
                                </div>
                            @endif
                            <input type="file" name="frame_foto" id="frame_foto" class="form-control @error('frame_foto') is-invalid @enderror">
                            <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah foto.</small>
                            @error('frame_foto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                Harga Frame
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <input type="number" name="frame_harga" class="form-control @error('frame_harga') is-invalid @enderror" 
                                           value="{{ old('frame_harga', $frame->frame_harga) }}" required>
                                    @error('frame_harga')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @foreach($kriterias as $kriteria)
                            @php
                                $isPriceKriteria = Str::contains(strtolower($kriteria->kriteria_nama), 'harga');
                                $selectedSubkriterias = $frame->frameSubkriterias
                                    ->where('kriteria_id', $kriteria->kriteria_id)
                                    ->pluck('subkriteria_id')
                                    ->toArray();
                            @endphp
                            
                            @if(!$isPriceKriteria)
                            <div class="card mb-3">
                                <div class="card-header">
                                    {{ $kriteria->kriteria_nama }}
                                </div>
                                <div class="card-body">
                                    @if($kriteria->subkriterias->count() > 0)
                                        @foreach($kriteria->subkriterias as $subkriteria)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="nilai[{{ $kriteria->kriteria_id }}][]" 
                                                       value="{{ $subkriteria->subkriteria_id }}" 
                                                       id="subkriteria{{ $subkriteria->subkriteria_id }}"
                                                       {{ in_array($subkriteria->subkriteria_id, $selectedSubkriterias) ? 'checked' : '' }}>
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

                        <div class="form-group text-center">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            <a href="{{ route('frame.index') }}" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection