@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>Tambah Subkriteria untuk {{ $selectedKriteria->kriteria_nama }}
                </h4>
            </div>
            
            <div class="card-body">
                <form action="{{ route('subkriteria.store') }}" method="POST">
                    @csrf

                    <div class="form-group mb-3">
                        <label for="kriteria_nama">Kriteria</label>
                        <input type="text" id="kriteria_nama" class="form-control" value="{{ $selectedKriteria->kriteria_nama }}" readonly>
                        <input type="hidden" name="kriteria_id" value="{{ $selectedKriteria->kriteria_id }}">
                    </div>

                    <div class="form-group mb-3">
                        <label>Tipe Subkriteria</label>
                        <select name="tipe_subkriteria" class="form-control" id="tipe-subkriteria"
                            data-old="{{ old('tipe_subkriteria') }}">
                            <option value="" selected disabled>Pilih Tipe Subkriteria</option>
                            <option value="teks">Teks Biasa</option>
                            <option value="angka">Angka</option>
                            <option value="rentang nilai">Rentang Nilai</option>
                        </select>
                        @if($errors->has('tipe_subkriteria'))
                            <div class="text-danger">{{ $errors->first('tipe_subkriteria') }}</div>
                        @endif
                    </div>
                    

                    <!-- Form untuk subkriteria teks -->
                    <div id="subkriteria-teks">
                        <div class="form-group mb-3">
                            <label for="subkriteria_nama_teks">Nama Subkriteria</label>
                            <input type="text" name="subkriteria_nama_teks" id="subkriteria_nama_teks" class="form-control" value="{{ old('subkriteria_nama_teks') }}">
                            @if($errors->has('subkriteria_nama_teks'))
                                <div class="text-danger">{{ $errors->first('subkriteria_nama_teks') }}</div>
                            @endif
                            @if($errors->has('subkriteria_nama'))
                                <div class="text-danger">{{ $errors->first('subkriteria_nama') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Form untuk subkriteria angka -->
                    <div id="subkriteria-angka" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="subkriteria_nilai_angka">Nilai Angka</label>
                                <input type="number" name="subkriteria_nilai_angka" id="subkriteria_nilai_angka" class="form-control" value="{{ old('subkriteria_nilai_angka') }}" step="any">
                                @if($errors->has('subkriteria_nilai_angka'))
                                    <div class="text-danger">{{ $errors->first('subkriteria_nilai_angka') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label for="subkriteria_satuan">Satuan (Opsional)</label>
                                <input type="text" name="subkriteria_satuan" id="subkriteria_satuan" class="form-control" value="{{ old('subkriteria_satuan') }}" placeholder="contoh: kg, cm, tahun">
                                @if($errors->has('subkriteria_satuan'))
                                    <div class="text-danger">{{ $errors->first('subkriteria_satuan') }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label>Preview</label>
                            <input type="text" id="preview-subkriteria-angka" class="form-control" readonly>
                            @if($errors->has('subkriteria_nama'))
                                <div class="text-danger">{{ $errors->first('subkriteria_nama') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Form untuk subkriteria numerik (rentang nilai) -->
                    <div id="subkriteria-numerik" style="display: none;">
                        <div class="form-group mb-3">
                            <label>Operator</label>
                            <select name="operator" class="form-control" id="operator">
                                <option value="<">Kurang dari (<)</option>
                                <option value="<=">Kurang dari sama dengan (<=)</option>
                                <option value=">">Lebih dari (>)</option>
                                <option value=">=">Lebih dari sama dengan (>=)</option>
                                <option value="between">Antara</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 form-group mb-3" id="nilai-minimum-container" style="display: none;">
                                <label>Nilai Minimum</label>
                                <input type="number" name="nilai_minimum" class="form-control nilai-numerik" value="{{ old('nilai_minimum') }}">
                                @if($errors->has('nilai_minimum'))
                                    <div class="text-danger">{{ $errors->first('nilai_minimum') }}</div>
                                @endif
                            </div>
                            
                            <div class="col-md-6 form-group mb-3" id="nilai-maksimum-container">
                                <label>Nilai Maksimum</label>
                                <input type="number" name="nilai_maksimum" class="form-control nilai-numerik" value="{{ old('nilai_maksimum') }}">
                                @if($errors->has('nilai_maksimum'))
                                    <div class="text-danger">{{ $errors->first('nilai_maksimum') }}</div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Preview</label>
                            <input type="text" id="preview-subkriteria" class="form-control" readonly>
                            <input type="hidden" name="subkriteria_nama_numerik" id="subkriteria_nama_numerik">
                            @if($errors->has('subkriteria_nama'))
                                <div class="text-danger">{{ $errors->first('subkriteria_nama') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="subkriteria_bobot">Bobot</label>
                        <select name="subkriteria_bobot" id="subkriteria_bobot" class="form-control" required>
                            <option value="" selected disabled>Pilih Bobot</option>
                            <option value="5" {{ old('subkriteria_bobot') == '5' ? 'selected' : '' }}>5</option>
                            <option value="4" {{ old('subkriteria_bobot') == '4' ? 'selected' : '' }}>4</option>
                            <option value="3" {{ old('subkriteria_bobot') == '3' ? 'selected' : '' }}>3</option>
                            <option value="2" {{ old('subkriteria_bobot') == '2' ? 'selected' : '' }}>2</option>
                            <option value="1" {{ old('subkriteria_bobot') == '1' ? 'selected' : '' }}>1</option>
                        </select>
                        @if($errors->has('subkriteria_bobot'))
                            <div class="text-danger">{{ $errors->first('subkriteria_bobot') }}</div>
                        @endif
                    </div>

                    <div class="form-group mb-3">
                        <label for="subkriteria_keterangan">Keterangan</label>
                        <textarea name="subkriteria_keterangan" id="subkriteria_keterangan"
                            class="form-control @error('subkriteria_keterangan') is-invalid @enderror"
                            rows="3" pattern="[A-Za-z\s\,\.]+" 
                            title="Hanya huruf dan spasi yang diperbolehkan" required>{{ old('subkriteria_keterangan') }}</textarea>
                        @if($errors->has('subkriteria_keterangan'))
                            <div class="text-danger">{{ $errors->first('subkriteria_keterangan') }}</div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        
                        <a href="{{ route('subkriteria.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/subkriteria.js') }}"></script>
@endsection