@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-glasses"></i> Tambah Frame
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('frame.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="card mb-3">
                        <div class="card-header">
                            Merek Frame
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <input type="text" name="frame_merek" id="frame_merek" class="form-control @error('frame_merek') is-invalid @enderror" 
                               value="{{ old('frame_merek') }}" placeholder="Masukkan merek frame" required>
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
                                <input type="file" name="frame_foto" id="frame_foto" 
                                       class="form-control @error('frame_foto') is-invalid @enderror" 
                                       accept=".jpg,.jpeg,.png" required>
                                <small class="form-text text-muted">Hanya menerima file gambar dengan format JPG, JPEG, atau PNG</small>
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
                                           value="{{ old('frame_harga') ? number_format(old('frame_harga'), 0, ',', '.') : '' }}" 
                                           placeholder="Masukkan harga frame" 
                                           required>
                                @error('frame_harga')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    @foreach($kriterias as $kriteria)
                        @php
                            $isPriceKriteria = Str::contains(strtolower($kriteria->kriteria_nama), 'harga');
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
                                                   {{ is_array(old('nilai.'.$kriteria->kriteria_id)) && in_array($subkriteria->subkriteria_id, old('nilai.'.$kriteria->kriteria_id)) ? 'checked' : '' }}>
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
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ route('frame.index') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection