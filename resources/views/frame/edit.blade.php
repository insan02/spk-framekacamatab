@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-glasses"></i> Edit Frame
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('frame.update', $frame->frame_id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card mb-3">
                        <div class="card-header">
                            Merek Frame
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <input type="text" name="frame_merek" id="frame_merek" class="form-control @error('frame_merek') is-invalid @enderror" 
                               value="{{ old('frame_merek', $frame->frame_merek) }}" placeholder="Masukkan merek frame" required>
                                @error('frame_merek')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            Foto Frame
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                @if($frame->frame_foto)
                                    <div class="mb-3">
                                        <img src="{{ asset('storage/'.$frame->frame_foto) }}" alt="{{ $frame->frame_merek }}" class="img-thumbnail" style="max-height: 200px;">
                                    </div>
                                @endif
                                <input type="file" name="frame_foto" id="frame_foto" 
                                       class="form-control @error('frame_foto') is-invalid @enderror" 
                                       accept=".jpg,.jpeg,.png">
                                <small class="form-text text-muted">Hanya menerima file gambar dengan format JPG, JPEG, atau PNG. Biarkan kosong jika tidak ingin mengubah foto.</small>
                                @error('frame_foto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            Harga Frame
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" name="frame_harga" id="frame_harga" 
                                           class="form-control @error('frame_harga') is-invalid @enderror" 
                                           value="{{ old('frame_harga', number_format($frame->frame_harga, 0, ',', '.')) }}" 
                                           placeholder="Masukkan harga frame" 
                                           required>
                                    @error('frame_harga')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            Lokasi Frame
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <input type="text" name="frame_lokasi" id="frame_lokasi" class="form-control @error('frame_lokasi') is-invalid @enderror" 
                               value="{{ old('frame_lokasi', $frame->frame_lokasi) }}" placeholder="Masukkan lokasi frame" required>
                                @error('frame_lokasi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    @foreach($kriterias as $kriteria)
                        @php
                            $isPriceKriteria = Str::contains(strtolower($kriteria->kriteria_nama), 'harga');
                            $frameSubkriterias = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id)->pluck('subkriteria_id')->toArray();
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
                                                   {{ in_array($subkriteria->subkriteria_id, $frameSubkriterias) ? 'checked' : '' }}>
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
@endsection