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
                            <option value="rentang nilai" {{ $subkriteria->tipe_subkriteria == 'rentang nilai' ? 'selected' : '' }}>Rentang Nilai</option>
                        </select>
                    </div>

                    <!-- Form untuk subkriteria teks -->
                    <div id="subkriteria-teks" {{ $subkriteria->tipe_subkriteria == 'rentang nilai' ? 'style=display:none;' : '' }}>
                        <div class="form-group mb-3">
                            <label for="subkriteria_nama">Nama Subkriteria</label>
                            <input type="text" name="subkriteria_nama" id="subkriteria_nama" class="form-control" value="{{ old('subkriteria_nama', $subkriteria->subkriteria_nama) }}">
                            @if($errors->has('subkriteria_nama'))
                                <div class="text-danger">{{ $errors->first('subkriteria_nama') }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Form untuk subkriteria numerik -->
                    <div id="subkriteria-numerik" {{ $subkriteria->tipe_subkriteria == 'teks' ? 'style=display:none;' : '' }}>
                        <div class="form-group mb-3">
                            <label>Operator</label>
                            <select name="operator" class="form-control" id="operator">
                                <option value="<" {{ strpos($subkriteria->subkriteria_nama, '<') === 0 && strpos($subkriteria->subkriteria_nama, '<=') === false ? 'selected' : '' }}>Kurang dari (<)</option>
                                <option value="<=" {{ strpos($subkriteria->subkriteria_nama, '<=') === 0 ? 'selected' : '' }}>Kurang dari sama dengan (<=)</option>
                                <option value=">" {{ strpos($subkriteria->subkriteria_nama, '>') === 0 && strpos($subkriteria->subkriteria_nama, '>=') === false ? 'selected' : '' }}>Lebih dari (>)</option>
                                <option value=">=" {{ strpos($subkriteria->subkriteria_nama, '>=') === 0 ? 'selected' : '' }}>Lebih dari sama dengan (>=)</option>
                                <option value="between" {{ strpos($subkriteria->subkriteria_nama, ' - ') !== false ? 'selected' : '' }}>Antara</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 form-group mb-3" id="nilai-minimum-container">
                                <label>Nilai Minimum</label>
                                <input type="number" name="nilai_minimum" class="form-control nilai-numerik" value="{{ old('nilai_minimum', isset($subkriteria->nilai_minimum) ? intval($subkriteria->nilai_minimum) : '') }}">
                            </div>
                            
                            <div class="col-md-6 form-group mb-3" id="nilai-maksimum-container">
                                <label>Nilai Maksimum</label>
                                <input type="number" name="nilai_maksimum" class="form-control nilai-numerik" value="{{ old('nilai_maksimum', isset($subkriteria->nilai_maksimum) ? intval($subkriteria->nilai_maksimum) : '') }}">
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label>Preview Nama Subkriteria</label>
                            <input type="text" id="preview-subkriteria" class="form-control" readonly value="{{ $subkriteria->subkriteria_nama }}">
                            <input type="hidden" name="subkriteria_nama_numerik" id="subkriteria_nama_numerik" value="{{ $subkriteria->subkriteria_nama }}">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="subkriteria_bobot">Bobot (1-5)</label>
                        <input type="number" name="subkriteria_bobot" id="subkriteria_bobot" class="form-control" min="1" max="5" required value="{{ old('subkriteria_bobot', $subkriteria->subkriteria_bobot) }}">
                        @if($errors->has('subkriteria_bobot'))
                            <div class="text-danger">{{ $errors->first('subkriteria_bobot') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-primary">Perbarui Subkriteria</button>
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
                $('#subkriteria-numerik').show();
                handleOperatorChange(); // Panggil fungsi untuk mengatur tampilan awal
            } else {
                $('#subkriteria-teks').show();
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
        
        // Form submit handler
        $('form').submit(function() {
            if ($('#tipe-subkriteria').val() === 'rentang nilai') {
                // Set nilai subkriteria_nama dari preview
                $('input[name="subkriteria_nama"]').val($('#preview-subkriteria').val());
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
        
        // Fungsi untuk memperbarui preview
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
            $('#subkriteria_nama_numerik').val(preview);
        }
        
        // Fungsi untuk memformat angka
        function formatNumber(num) {
            if (!num) return '';
            return new Intl.NumberFormat('id-ID').format(num);
        }
        
        // Inisialisasi tampilan berdasarkan nilai yang dipilih
        handleOperatorChange();
    });
</script>
@endpush
@endsection