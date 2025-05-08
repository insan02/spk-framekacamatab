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
                                
                                <!-- Tambahkan preview gambar jika ada -->
                                @if(session('temp_image') && Storage::disk('public')->exists(session('temp_image')))
                                    <div class="mt-3">
                                        <p>Foto yang sudah diupload:</p>
                                        <img src="{{ asset('storage/' . session('temp_image')) }}" 
                                            alt="Preview Foto Frame" 
                                            class="img-thumbnail" 
                                            style="max-height: 200px;">
                                        <input type="hidden" name="existing_temp_image" value="{{ session('temp_image') }}">
                                    </div>
                                @endif
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
                               value="{{ old('frame_lokasi') }}" placeholder="Masukkan lokasi frame" required>
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
                                    <!-- Cek apakah ada subkriteria dengan tipe rentang nilai -->
                                    @php
                                        $hasRangeType = $kriteria->subkriterias->contains('tipe_subkriteria', 'rentang nilai');
                                    @endphp
                                    
                                    @if($hasRangeType)
                                        <!-- Input type selector for range-type criteria -->
                                        <div class="mb-3">
                                            <div class="btn-group" role="group">
                                                @php
                                                    $oldInputType = old('input_type.'.$kriteria->kriteria_id);
                                                @endphp
                                                <button type="button" class="btn btn-outline-primary input-type-btn {{ ($oldInputType == 'checkbox' || !$oldInputType) ? 'active' : '' }}" 
                                                    data-kriteria="{{ $kriteria->kriteria_id }}" data-type="checkbox">
                                                    <i class="fas fa-check-square"></i> Pilihan
                                                </button>
                                                <button type="button" class="btn btn-outline-primary input-type-btn {{ $oldInputType == 'manual' ? 'active' : '' }}" 
                                                    data-kriteria="{{ $kriteria->kriteria_id }}" data-type="manual">
                                                    <i class="fas fa-keyboard"></i> Input Manual
                                                </button>
                                            </div>
                                            <input type="hidden" name="input_type[{{ $kriteria->kriteria_id }}]" 
                                                id="input_type_{{ $kriteria->kriteria_id }}" 
                                                value="{{ $oldInputType ?: 'checkbox' }}">
                                        </div>
                                        
                                        <!-- Manual input for range values with preview -->
                                        <div class="form-group manual-input-group" id="manual_input_{{ $kriteria->kriteria_id }}" 
                                            style="display: {{ $oldInputType == 'manual' ? 'block' : 'none' }};">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Masukkan Nilai {{ $kriteria->kriteria_nama }}:</label>
                                                    <input type="number" step="0.01" 
                                                        class="form-control manual-nilai-input" 
                                                        name="nilai_manual[{{ $kriteria->kriteria_id }}]" 
                                                        id="nilai_manual_{{ $kriteria->kriteria_id }}"
                                                        value="{{ old('nilai_manual.'.$kriteria->kriteria_id) }}" 
                                                        placeholder="Masukkan nilai"
                                                        data-kriteria="{{ $kriteria->kriteria_id }}">
                                                    <small class="form-text text-muted">Gunakan . (titik) untuk koma</small>
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
                                        style="display: {{ ($hasRangeType && old('input_type.'.$kriteria->kriteria_id) == 'manual') ? 'none' : 'block' }};">
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
                                    </div>
                                @else
                                    <p class="text-muted">Belum ada subkriteria</p>
                                @endif
                            </div>
                        </div>
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

@push('scripts')
<script>
    // Preview gambar saat memilih file baru
    document.getElementById('frame_foto').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                // Hapus preview sebelumnya jika ada
                const existingPreview = document.querySelector('.image-preview');
                if (existingPreview) {
                    existingPreview.remove();
                }
                
                // Buat elemen preview baru
                const previewDiv = document.createElement('div');
                previewDiv.className = 'mt-3 image-preview';
                previewDiv.innerHTML = `
                    <p>Preview Foto Baru:</p>
                    <img src="${e.target.result}" 
                         alt="Preview Foto Frame" 
                         class="img-thumbnail" 
                         style="max-height: 200px;">
                `;
                
                // Sisipkan setelah input file
                e.target.parentNode.appendChild(previewDiv);
            }
            reader.readAsDataURL(file);
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

    // Format number function (copied from your existing subkriteria.js)
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