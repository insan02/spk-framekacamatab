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
                        <select name="tipe_subkriteria" class="form-control" id="tipe-subkriteria">
                            <option value="teks">Teks Biasa</option>
                            <option value="angka">Angka</option>
                            <option value="rentang nilai">Rentang Nilai</option>
                        </select>
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
                        <label for="subkriteria_bobot">Bobot (1-5)</label>
                        <input type="number" name="subkriteria_bobot" id="subkriteria_bobot" class="form-control" min="1" max="5" required value="{{ old('subkriteria_bobot') }}">
                        @if($errors->has('subkriteria_bobot'))
                            <div class="text-danger">{{ $errors->first('subkriteria_bobot') }}</div>
                        @endif
                    </div>

                    <div class="form-group mb-3">
                        <label for="subkriteria_keterangan">Keterangan Bobot</label>
                        <textarea name="subkriteria_keterangan" id="subkriteria_keterangan"
                            class="form-control @error('subkriteria_keterangan') is-invalid @enderror"
                            rows="3" pattern="[A-Za-z\s\,\.]+" 
                            title="Hanya huruf dan spasi yang diperbolehkan" required>{{ old('subkriteria_keterangan') }}</textarea>
                        @if($errors->has('subkriteria_keterangan'))
                            <div class="text-danger">{{ $errors->first('subkriteria_keterangan') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Toggle tampilan form berdasarkan tipe subkriteria
        $('#tipe-subkriteria').change(function() {
            if ($(this).val() === 'rentang nilai') {
                $('#subkriteria-teks').hide();
                $('#subkriteria-angka').hide();
                $('#subkriteria-numerik').show();
                handleOperatorChange(); // Panggil fungsi untuk mengatur tampilan awal
            } else if ($(this).val() === 'angka') {
                $('#subkriteria-teks').hide();
                $('#subkriteria-angka').show();
                $('#subkriteria-numerik').hide();
                updateAngkaPreview();
            } else {
                $('#subkriteria-teks').show();
                $('#subkriteria-angka').hide();
                $('#subkriteria-numerik').hide();
            }
        });
        
        // Logika tampilan berdasarkan operator
        $('#operator').change(function() {
            handleOperatorChange();
        });
        
        // Update preview saat nilai berubah
        $('.nilai-numerik').on('input', function() {
            updatePreview();
        });
        
        // Update preview angka
        $('#subkriteria_nilai_angka, #subkriteria_satuan').on('input', function() {
            updateAngkaPreview();
        });
        
        // Form submit handler
        $('form').submit(function() {
            if ($('#tipe-subkriteria').val() === 'rentang nilai') {
                // Set nilai subkriteria_nama dari preview
                $('input[name="subkriteria_nama_numerik"]').val($('#preview-subkriteria').val());
            }
        });
        
        // Fungsi untuk mengubah tampilan berdasarkan operator
        function handleOperatorChange() {
            let operator = $('#operator').val();
            
            if (operator === 'between') {
                $('#nilai-minimum-container, #nilai-maksimum-container').show();
            } else if (operator === '<' || operator === '<=') {
                $('#nilai-minimum-container').hide();
                $('#nilai-maksimum-container').show();
            } else {
                $('#nilai-minimum-container').show();
                $('#nilai-maksimum-container').hide();
            }
            
            updatePreview();
        }
        
        // Fungsi untuk memperbarui preview rentang nilai
        function updatePreview() {
            let operator = $('#operator').val();
            let min = $('input[name="nilai_minimum"]').val();
            let max = $('input[name="nilai_maksimum"]').val();
            let preview = '';
            
            if (operator === 'between' && min && max) {
                preview = formatNumber(min) + ' - ' + formatNumber(max);
            } else if ((operator === '<' || operator === '<=') && max) {
                preview = operator + ' ' + formatNumber(max);
            } else if ((operator === '>' || operator === '>=') && min) {
                preview = operator + ' ' + formatNumber(min);
            }
            
            $('#preview-subkriteria').val(preview);
        }
        
        // Fungsi untuk memperbarui preview nilai angka
        function updateAngkaPreview() {
            let angka = $('#subkriteria_nilai_angka').val();
            let satuan = $('#subkriteria_satuan').val();
            let preview = '';
            
            if (angka) {
                preview = formatNumber(angka);
                if (satuan) {
                    preview += ' ' + satuan;
                }
            }
            
            $('#preview-subkriteria-angka').val(preview);
        }
        
        // Fungsi untuk memformat angka
        function formatNumber(num) {
            if (!num) return '';
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        // Inisialisasi tampilan berdasarkan nilai yang dipilih
        handleOperatorChange();
        updateAngkaPreview();

        // Inisialisasi tampilan berdasarkan nilai old() jika ada error
        const oldTipeSubkriteria = "{{ old('tipe_subkriteria') }}";
        if (oldTipeSubkriteria) {
            $('#tipe-subkriteria').val(oldTipeSubkriteria).trigger('change');
        }
    });
</script>
@endpush
@endsection