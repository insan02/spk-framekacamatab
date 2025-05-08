@extends('layouts.app')

@section('content')
<div class="container">
    <div class="container-fluid">
        <div class="mb-3">
            <a href="{{ route('subkriteria.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-edit me-2"></i>Edit Subkriteria
                </h4>
            </div>
            <div class="card-body">
                <form action="{{ route('subkriteria.update', $subkriteria->subkriteria_id) }}" method="POST" id="form-edit">
                    @csrf
                    @method('PUT')

                    <div class="form-group mb-3">
                        <label for="kriteria_nama">Kriteria</label>
                        <input type="text" class="form-control" 
                               value="{{ $subkriteria->kriteria->kriteria_nama }}" 
                               readonly>
                        <input type="hidden" name="kriteria_id" value="{{ $subkriteria->kriteria_id }}">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Tipe Subkriteria</label>
                        <select name="tipe_subkriteria" class="form-control" id="tipe-subkriteria">
                            <option value="teks" {{ $subkriteria->tipe_subkriteria == 'teks' ? 'selected' : '' }}>Teks Biasa</option>
                            <option value="angka" {{ $subkriteria->tipe_subkriteria == 'angka' ? 'selected' : '' }}>Angka</option>
                            <option value="rentang nilai" {{ $subkriteria->tipe_subkriteria == 'rentang nilai' ? 'selected' : '' }}>Rentang Nilai</option>
                        </select>
                    </div>

                    <!-- Form untuk subkriteria teks -->
                    <div id="subkriteria-teks" {{ $subkriteria->tipe_subkriteria != 'teks' ? 'style=display:none;' : '' }}>
                        <div class="form-group mb-3">
                            <label for="subkriteria_nama_teks">Nama Subkriteria</label>
                            <input type="text" name="subkriteria_nama_teks" id="subkriteria_nama_teks" 
                                class="form-control" value="{{ old('subkriteria_nama_teks', $subkriteria->tipe_subkriteria == 'teks' ? $subkriteria->subkriteria_nama : '') }}">
                            @if($errors->has('subkriteria_nama_teks'))
                                <div class="text-danger">{{ $errors->first('subkriteria_nama_teks') }}</div>
                            @endif
                            @if($errors->has('subkriteria_nama'))
                                <div class="text-danger">{{ $errors->first('subkriteria_nama') }}</div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Form untuk subkriteria angka -->
                    <div id="subkriteria-angka" {{ $subkriteria->tipe_subkriteria != 'angka' ? 'style=display:none;' : '' }}>
                        <div class="row">
                            <div class="col-md-6 form-group mb-3">
                                <label for="subkriteria_nilai_angka">Nilai Angka</label>
                                @php
                                    $nilai_angka = old('subkriteria_nilai_angka');
                                    if (!$nilai_angka && $subkriteria->tipe_subkriteria == 'angka') {
                                        $nilai_angka = $subkriteria->nilai_minimum;
                                    }
                                @endphp
                                <input type="number" name="subkriteria_nilai_angka" id="subkriteria_nilai_angka" class="form-control" 
                                value="{{ $nilai_angka }}" step="any">
                                @if($errors->has('subkriteria_nilai_angka'))
                                    <div class="text-danger">{{ $errors->first('subkriteria_nilai_angka') }}</div>
                                @endif
                            </div>
                            <div class="col-md-6 form-group mb-3">
                                <label for="subkriteria_satuan">Satuan (Opsional)</label>
                                @php
                                    $satuan = old('subkriteria_satuan');
                                    if (!$satuan && $subkriteria->tipe_subkriteria == 'angka') {
                                        $nama_parts = explode(' ', $subkriteria->subkriteria_nama);
                                        if (count($nama_parts) > 1) {
                                            array_shift($nama_parts); // Remove first element (the number)
                                            $satuan = implode(' ', $nama_parts);
                                        }
                                    }
                                @endphp
                                <input type="text" name="subkriteria_satuan" id="subkriteria_satuan" class="form-control" 
                                value="{{ $satuan }}" placeholder="contoh: kg, cm, tahun">
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
                    <div id="subkriteria-numerik" {{ $subkriteria->tipe_subkriteria != 'rentang nilai' ? 'style=display:none;' : '' }}>
                        <div class="form-group mb-3">
                            <label>Operator</label>
                            @php
                                $operator = old('operator', $subkriteria->operator);
                                // Detect operator from name if not set
                                if (!$operator && $subkriteria->tipe_subkriteria == 'rentang nilai') {
                                    if (strpos($subkriteria->subkriteria_nama, ' - ') !== false) {
                                        $operator = 'between';
                                    } elseif (strpos($subkriteria->subkriteria_nama, '<=') === 0) {
                                        $operator = '<=';
                                    } elseif (strpos($subkriteria->subkriteria_nama, '<') === 0) {
                                        $operator = '<';
                                    } elseif (strpos($subkriteria->subkriteria_nama, '>=') === 0) {
                                        $operator = '>=';
                                    } elseif (strpos($subkriteria->subkriteria_nama, '>') === 0) {
                                        $operator = '>';
                                    }
                                }
                            @endphp
                            <select name="operator" class="form-control" id="operator">
                                <option value="<" {{ $operator == '<' ? 'selected' : '' }}>Kurang dari (<)</option>
                                <option value="<=" {{ $operator == '<=' ? 'selected' : '' }}>Kurang dari sama dengan (<=)</option>
                                <option value=">" {{ $operator == '>' ? 'selected' : '' }}>Lebih dari (>)</option>
                                <option value=">=" {{ $operator == '>=' ? 'selected' : '' }}>Lebih dari sama dengan (>=)</option>
                                <option value="between" {{ $operator == 'between' ? 'selected' : '' }}>Antara</option>
                            </select>
                            @if($errors->has('operator'))
                                <div class="text-danger">{{ $errors->first('operator') }}</div>
                            @endif
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 form-group mb-3" id="nilai-minimum-container">
                                <label>Nilai Minimum</label>
                                @php
                                    $nilai_minimum = old('nilai_minimum', $subkriteria->nilai_minimum);
                                @endphp
                                <input type="number" name="nilai_minimum" class="form-control nilai-numerik" value="{{ $nilai_minimum }}">
                                @if($errors->has('nilai_minimum'))
                                    <div class="text-danger">{{ $errors->first('nilai_minimum') }}</div>
                                @endif
                            </div>
                            
                            <div class="col-md-6 form-group mb-3" id="nilai-maksimum-container">
                                <label>Nilai Maksimum</label>
                                @php
                                    $nilai_maksimum = old('nilai_maksimum', $subkriteria->nilai_maksimum);
                                @endphp
                                <input type="number" name="nilai_maksimum" class="form-control nilai-numerik" value="{{ $nilai_maksimum }}">
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
                            <option value="" disabled>Pilih Bobot</option>
                            <option value="5" {{ old('subkriteria_bobot', $subkriteria->subkriteria_bobot) == '5' ? 'selected' : '' }}>5</option>
                            <option value="4" {{ old('subkriteria_bobot', $subkriteria->subkriteria_bobot) == '4' ? 'selected' : '' }}>4</option>
                            <option value="3" {{ old('subkriteria_bobot', $subkriteria->subkriteria_bobot) == '3' ? 'selected' : '' }}>3</option>
                            <option value="2" {{ old('subkriteria_bobot', $subkriteria->subkriteria_bobot) == '2' ? 'selected' : '' }}>2</option>
                            <option value="1" {{ old('subkriteria_bobot', $subkriteria->subkriteria_bobot) == '1' ? 'selected' : '' }}>1</option>
                        </select>
                        @if($errors->has('subkriteria_bobot'))
                            <div class="text-danger">{{ $errors->first('subkriteria_bobot') }}</div>
                        @endif
                    </div>
                    

                    <div class="form-group mb-3">
                        <label for="subkriteria_keterangan">Keterangan</label>
                        <textarea name="subkriteria_keterangan" id="subkriteria_keterangan"
                            class="form-control @error('subkriteria_keterangan') is-invalid @enderror"
                            rows="3" required>{{ old('subkriteria_keterangan', $subkriteria->subkriteria_keterangan) }}</textarea>
                        @if($errors->has('subkriteria_keterangan'))
                            <div class="text-danger">{{ $errors->first('subkriteria_keterangan') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary">Perbarui Subkriteria</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/subkriteria.js') }}"></script>
@endsection