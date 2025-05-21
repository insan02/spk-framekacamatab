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
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle"></i> Hanya menerima file gambar dengan format <strong>JPG, JPEG,</strong> atau <strong>PNG</strong>.<br>
                                    <i class="fas fa-info-circle"></i> Posisikan frame di <strong>tengah-tengah</strong>.<br>
                                    <i class="fas fa-info-circle"></i> Gunakan latar belakang <strong>berwarna putih</strong>.
                                </small>
                                @error('frame_foto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Bagian untuk preview foto baru (akan muncul saat pengguna memilih foto) -->
                    <div id="preview-container" style="display: none;">
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-eye"></i> Preview Foto Baru
                            </div>
                            <div class="card-body text-center">
                                <img id="preview-image" src="#" alt="Preview Foto Frame" class="img-thumbnail" style="max-height: 200px;">
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
                        <div class="card mb-3">
                            <div class="card-header">
                                {{ $kriteria->kriteria_nama }}
                            </div>
                            <div class="card-body">
                                @if($kriteria->subkriterias->count() > 0)
                                    @php
                                        // Get existing subkriteria selections
                                        $frameSubkriterias = $frame->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id);
                                        $selectedSubkriteriaIds = $frameSubkriterias->pluck('subkriteria_id')->toArray();
                                        
                                        // Check if any range-type subkriteria exists
                                        $hasRangeType = $kriteria->subkriterias->contains('tipe_subkriteria', 'rentang nilai');
                                        
                                        // Get old input type or determine from existing data
                                        $oldInputType = old('input_type.'.$kriteria->kriteria_id);
                                        
                                        // Check if we have a manual value stored for this criteria
                                        $manualValue = null;
                                        $hasManualValue = false;
                                        
                                        // Find any manual value entry for this criteria
                                        foreach ($frameSubkriterias as $frameSub) {
                                            if (!is_null($frameSub->manual_value)) {
                                                $manualValue = $frameSub->manual_value;
                                                $hasManualValue = true;
                                                break;
                                            }
                                        }
                                        
                                        // Set default input type based on presence of manual value
                                        if ($oldInputType === null) {
                                            $activeInputType = $hasManualValue ? 'manual' : 'checkbox';
                                        } else {
                                            $activeInputType = $oldInputType;
                                        }
                                        
                                        // If we have an old manual value from form validation, use that
                                        if (old('nilai_manual.'.$kriteria->kriteria_id)) {
                                            $manualValue = old('nilai_manual.'.$kriteria->kriteria_id);
                                        }
                                    @endphp

                                    <small class="text-muted d-block mb-3 fst-italic">
                                        <i class="fas fa-info-circle"></i> Centang semua yang sesuai dengan frame, Anda dapat memilih lebih dari satu subkriteria jika frame memiliki beberapa subkriteria.
                                    </small>

                                    @if($hasRangeType)
                                        <!-- Input type selector for range-type criteria -->
                                        <div class="mb-3">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-primary input-type-btn {{ $activeInputType == 'checkbox' ? 'active' : '' }}" 
                                                    data-kriteria="{{ $kriteria->kriteria_id }}" data-type="checkbox">
                                                    <i class="fas fa-check-square"></i> Pilihan
                                                </button>
                                                <button type="button" class="btn btn-outline-primary input-type-btn {{ $activeInputType == 'manual' ? 'active' : '' }}" 
                                                    data-kriteria="{{ $kriteria->kriteria_id }}" data-type="manual">
                                                    <i class="fas fa-keyboard"></i> Input
                                                </button>
                                            </div>
                                            <input type="hidden" name="input_type[{{ $kriteria->kriteria_id }}]" 
                                                id="input_type_{{ $kriteria->kriteria_id }}" 
                                                value="{{ $activeInputType }}">
                                        </div>
                                        
                                        <!-- Manual input for range values with preview -->
                                        <div class="form-group manual-input-group" id="manual_input_{{ $kriteria->kriteria_id }}" 
                                            style="display: {{ $activeInputType == 'manual' ? 'block' : 'none' }};">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Masukkan Nilai {{ $kriteria->kriteria_nama }}:</label>
                                                    <input type="number" step="0.01" 
                                                        class="form-control manual-nilai-input" 
                                                        name="nilai_manual[{{ $kriteria->kriteria_id }}]" 
                                                        id="nilai_manual_{{ $kriteria->kriteria_id }}"
                                                        value="{{ $manualValue }}" 
                                                        placeholder="Masukkan nilai"
                                                        data-kriteria="{{ $kriteria->kriteria_id }}">
                                                        <small class="form-text text-muted">Gunakan . (titik) sebagai koma</small>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Preview:</label>
                                                    <input type="text" 
                                                        class="form-control"
                                                        id="preview_nilai_{{ $kriteria->kriteria_id }}"
                                                        readonly>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- Checkbox selections for criteria -->
                                    <div class="checkbox-group" id="checkbox_group_{{ $kriteria->kriteria_id }}" 
                                        style="display: {{ ($hasRangeType && $activeInputType == 'manual') ? 'none' : 'block' }};">
                                        @foreach($kriteria->subkriterias as $subkriteria)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                    name="nilai[{{ $kriteria->kriteria_id }}][]" 
                                                    value="{{ $subkriteria->subkriteria_id }}" 
                                                    id="subkriteria{{ $subkriteria->subkriteria_id }}"
                                                    {{ in_array($subkriteria->subkriteria_id, $selectedSubkriteriaIds) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="subkriteria{{ $subkriteria->subkriteria_id }}">
                                                        {{ $subkriteria->subkriteria_nama }}
                                                    </label>   
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted">Belum ada subkriteria</p>
                                @endif
                            </div>
                        </div>
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

@push('scripts')
<script>
    // Preview gambar saat memilih file baru
document.getElementById('frame_foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Tampilkan container preview
            previewContainer.style.display = 'block';
            
            // Atur sumber gambar preview
            previewImage.src = e.target.result;
        }
        reader.readAsDataURL(file);
    } else {
        // Sembunyikan preview jika tidak ada file yang dipilih
        previewContainer.style.display = 'none';
    }
});

    // Input type selector (checkbox vs manual input)
    document.querySelectorAll('.input-type-btn').forEach(button => {
        button.addEventListener('click', function() {
            const kriteriaId = this.getAttribute('data-kriteria');
            const inputType = this.getAttribute('data-type');
            
            // Update hidden input value
            document.getElementById(`input_type_${kriteriaId}`).value = inputType;
            
            // Activate the button
            document.querySelectorAll(`.input-type-btn[data-kriteria="${kriteriaId}"]`).forEach(btn => {
                btn.classList.remove('active');
            });
            this.classList.add('active');
            
            // Show/hide relevant input method
            if (inputType === 'manual') {
                document.getElementById(`manual_input_${kriteriaId}`).style.display = 'block';
                document.getElementById(`checkbox_group_${kriteriaId}`).style.display = 'none';
            } else {
                document.getElementById(`manual_input_${kriteriaId}`).style.display = 'none';
                document.getElementById(`checkbox_group_${kriteriaId}`).style.display = 'block';
            }
        });
    });

    // Format number function
    function formatNumber(num) {
        if (!num) return '';
        return new Intl.NumberFormat('id-ID').format(num);
    }

    // Preview for manual input values
    document.querySelectorAll('.manual-nilai-input').forEach(input => {
        input.addEventListener('input', function() {
            const kriteriaId = this.getAttribute('data-kriteria');
            const value = this.value;
            const previewElement = document.getElementById(`preview_nilai_${kriteriaId}`);
            
            if (value) {
                previewElement.value = formatNumber(value);
            } else {
                previewElement.value = '';
            }
        });
        
        // Trigger input event on page load to initialize preview
        const event = new Event('input');
        input.dispatchEvent(event);
    });
</script>
@endpush