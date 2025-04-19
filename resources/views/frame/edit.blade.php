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
                @if(session('similarity_results'))
                    @php
                        $similarityResults = session('similarity_results');
                        $similarFrame = $similarityResults['similarFrame'];
                    @endphp
                    <!-- Similarity Warning Alert -->
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> <strong>Perhatian!</strong> Sistem mendeteksi frame yang Anda edit memiliki kemiripan dengan frame yang sudah ada.
                    </div>
                    
                    <!-- Similarity Details Panel -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Info Kemiripan</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                @if(isset($similarityResults['similarityDetails']['image']) && $similarityResults['similarityDetails']['image']['similar'])
                                    <li class="list-group-item list-group-item-warning">
                                        <strong>Foto Frame:</strong> {{ $similarityResults['similarityDetails']['image']['message'] }}
                                        @if(isset($similarityResults['similarityDetails']['image']['frame_id']))
                                            (ID Frame: {{ $similarityResults['similarityDetails']['image']['frame_id'] }})
                                        @endif
                                    </li>
                                @endif
                                
                                @if(isset($similarityResults['similarityDetails']['data']) && $similarityResults['similarityDetails']['data']['similar'])
                                    <li class="list-group-item list-group-item-warning">
                                        <strong>Data Frame:</strong> {{ $similarityResults['similarityDetails']['data']['message'] }}
                                        @if(isset($similarityResults['similarityDetails']['data']['frames']) && count($similarityResults['similarityDetails']['data']['frames']) > 0)
                                            (ID Frame: {{ implode(', ', array_slice($similarityResults['similarityDetails']['data']['frames'], 0, 3)) }}
                                            {{ count($similarityResults['similarityDetails']['data']['frames']) > 3 ? '...' : '' }})
                                        @endif
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Similar Frame Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Frame Serupa yang Ditemukan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header bg-secondary text-white text-center">
                                            <h6 class="mb-0">ID: {{ $similarFrame->frame_id }} - {{ $similarFrame->frame_merek }}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center">
                                                @if($similarFrame->frame_foto && Storage::disk('public')->exists($similarFrame->frame_foto))
                                                    <div class="d-flex justify-content-center align-items-center" style="height: 150px;">
                                                        <img src="{{ asset('storage/' . $similarFrame->frame_foto) }}" 
                                                             alt="{{ $similarFrame->frame_merek }}" 
                                                             class="img-thumbnail" 
                                                             style="max-height: 130px; max-width: 100%; object-fit: contain;">
                                                    </div>
                                                @else
                                                    <div class="text-muted d-flex justify-content-center align-items-center" style="height: 150px;">
                                                        Gambar tidak tersedia
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <table class="table table-sm table-bordered">
                                                <tr>
                                                    <th width="80" class="bg-light">Merek</th>
                                                    <td>{{ $similarFrame->frame_merek }}</td>
                                                </tr>
                                                <tr>
                                                    <th width="80" class="bg-light">Harga</th>
                                                    <td>Rp {{ number_format($similarFrame->frame_harga, 0, ',', '.') }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-light">Lokasi</th>
                                                    <td>{{ $similarFrame->frame_lokasi }}</td>
                                                </tr>
                                            </table>
                                            
                                            <!-- Show existing criteria information -->
                                            @if($similarFrame->frameSubkriterias->count() > 0)
                                                <div class="mt-2">
                                                    <h6 class="border-bottom pb-2">Kriteria Frame:</h6>
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th>Kriteria</th>
                                                                <th>Nilai</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @php
                                                                $groupedCriteria = $similarFrame->frameSubkriterias->groupBy('kriteria_id');
                                                            @endphp
                                                            
                                                            @foreach($groupedCriteria as $kriteria_id => $frameSubkriterias)
                                                                @php
                                                                    $kriteria = $frameSubkriterias->first()->kriteria;
                                                                    $subkriteriaNames = [];
                                                                    foreach($frameSubkriterias as $fs) {
                                                                        if($fs->subkriteria) {
                                                                            $subkriteriaNames[] = $fs->subkriteria->subkriteria_nama;
                                                                        }
                                                                    }
                                                                @endphp
                                                                <tr>
                                                                    <td><strong>{{ $kriteria->kriteria_nama ?? 'Kriteria #'.$kriteria_id }}</strong></td>
                                                                    <td>{{ implode(', ', $subkriteriaNames) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                            
                                            <div class="text-center mt-3">
                                                <a href="{{ route('frame.show', $similarFrame->frame_id) }}" 
                                                   class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-eye"></i> Lihat Detail
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i> Ditemukan kemiripan dengan frame yang sudah ada. Anda dapat melanjutkan penyimpanan jika yakin frame ini berbeda.
                            </div>
                        </div>
                    </div>
                @endif
                
                <form action="{{ route('frame.update', $frame->frame_id) }}" method="POST" id="form-edit" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <!-- Add this to track confirmed similarity status -->
                    @if(session('confirmed_similarity'))
                        <input type="hidden" name="confirmed_similarity" value="1">
                    @endif
                    
                    <!-- Display temp image if exists (preview of uploaded image after similarity warning) -->
                    @if(session('temp_edit_image'))
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-image"></i> Foto baru yang Anda unggah akan diterapkan saat Anda menyimpan perubahan.
                        </div>
                    @endif
                    
                    <div class="row">
                        <!-- Left Column: Frame Details -->
                        <div class="col-md-6">
                            <!-- Merek Frame Card -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    Merek Frame
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <input type="text" class="form-control @error('frame_merek') is-invalid @enderror" 
                                               id="frame_merek" name="frame_merek" 
                                               value="{{ old('frame_merek', $frame->frame_merek) }}" required>
                                        @error('frame_merek')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Harga Frame Card -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    Harga Frame
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <input type="text" class="form-control @error('frame_harga') is-invalid @enderror" 
                                               id="frame_harga" name="frame_harga" 
                                               value="{{ old('frame_harga', number_format($frame->frame_harga, 0, ',', '.')) }}" 
                                               required>
                                        @error('frame_harga')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Foto Frame Card -->
                            <div class="card mb-3"> 
                                <div class="card-header">
                                    Foto Frame
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        @if(session('temp_edit_image'))
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . session('temp_edit_image')) }}?v={{ time() }}" 
                                                     alt="Foto baru yang diupload" 
                                                     class="img-thumbnail" 
                                                     style="max-height: 200px;">
                                                <p class="small text-muted">Foto baru yang akan diterapkan.</p>
                                            </div>
                                        @elseif($frame->frame_foto && file_exists(public_path('storage/' . $frame->frame_foto)))
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . $frame->frame_foto) }}?v={{ time() }}" 
                                                     alt="{{ $frame->frame_merek }}" 
                                                     class="img-thumbnail" 
                                                     style="max-height: 200px;">
                                                <p class="small text-muted">Foto saat ini. Unggah foto baru untuk menggantinya.</p>
                                            </div>
                                        @endif
                                        <label for="frame_foto">Unggah Foto</label>
                                        <div>
                                            <input type="file" class="form-control-file @error('frame_foto') is-invalid @enderror" 
                                                   id="frame_foto" name="frame_foto" 
                                                   accept=".jpg,.jpeg,.png">
                                            <small class="form-text text-muted d-block mt-1">Hanya menerima file gambar dengan format JPG, JPEG, atau PNG</small>
                                        </div>
                                        @error('frame_foto')
                                            <span class="invalid-feedback d-block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Lokasi Frame Card -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    Lokasi Frame
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <input type="text" class="form-control @error('frame_lokasi') is-invalid @enderror" 
                                               id="frame_lokasi" name="frame_lokasi" 
                                               value="{{ old('frame_lokasi', $frame->frame_lokasi) }}" required>
                                        @error('frame_lokasi')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                        <!-- Right Column: Kriteria -->
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
                    
                    <div class="form-group text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('frame.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
                
                @if(session('similarity_results'))
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Catatan:</strong> Melanjutkan penyimpanan akan mengubah frame ini meskipun terdapat kemiripan dengan frame lain.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Format currency input
    document.addEventListener('DOMContentLoaded', function() {
        const hargaInput = document.getElementById('frame_harga');
        
        if (hargaInput) {
            // Format on page load
            let value = hargaInput.value;
            value = value.replace(/\./g, '');
            hargaInput.value = formatCurrency(value);
            
            // Format on input
            hargaInput.addEventListener('input', function(e) {
                let value = e.target.value;
                value = value.replace(/\./g, '');
                e.target.value = formatCurrency(value);
            });
            
            // Format before form submission
            document.getElementById('form-edit').addEventListener('submit', function() {
                let value = hargaInput.value;
                hargaInput.value = value.replace(/\./g, '');
            });
        }
        
        function formatCurrency(value) {
            // Remove non-digit characters
            value = value.replace(/\D/g, '');
            
            // Format number with thousand separators
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    });
</script>
@endpush
@endsection