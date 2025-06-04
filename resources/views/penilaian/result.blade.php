<div class="container-fluid">
    {{-- Customer Information Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user me-2"></i>Data Pelanggan
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <strong class="me-2">Nama:</strong> 
                                <span>{{ $customer->name }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <strong class="me-2">No HP:</strong> 
                                <span>{{ $customer->phone }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <strong class="me-2">Alamat:</strong> 
                                <span>{{ $customer->address }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kriteria Pilihan --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-list-alt me-2"></i>Kriteria Pilihan Pelanggan
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <h5>Kriteria Terpilih</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kriteria</th>
                                        <th>Subkriteria Dipilih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kriteria_dipilih as $kriteria => $subkriteria)
                                    <tr>
                                        <td>{{ $kriteria }}</td>
                                        <td>{{ $subkriteria }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bobot Kriteria Pelanggan --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-list-alt me-2"></i>Bobot Kriteria Pelanggan
                    </h4>
                </div>
                <div class="card-body">
    <div class="row g-3">
        <div class="col-md-12">
            <h5>Bobot Kriteria</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Kriteria</th>
                        <th>Bobot Awal</th>
                        <th>Bobot Normalisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($perhitungan['kriterias'] as $kriteria)
                    <tr>
                        <td>{{ $kriteria->kriteria_nama }}</td>
                        <td>{{ $perhitungan['bobotKriteriaUser'][$kriteria->kriteria_id] }}</td>
                        <td>
                            {{ $perhitungan['bobotKriteria'][$kriteria->kriteria_id] }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="alert alert-info">
                <strong>Total Bobot Normalisasi: 1</strong> 
            </div>
        </div>
    </div>
</div>
            </div>
        </div>
    </div>

    {{-- Calculation Details Tabs --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-calculator me-2"></i>Detail Perhitungan
            </h4>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="calculationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profileFrame" type="button" role="tab">Nilai Profile Frame</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="gap-tab" data-bs-toggle="tab" data-bs-target="#perhitunganGap" type="button" role="tab">Perhitungan GAP</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="konversi-tab" data-bs-toggle="tab" data-bs-target="#konversiGap" type="button" role="tab">Konversi Nilai GAP</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="akhir-tab" data-bs-toggle="tab" data-bs-target="#nilaiAkhir" type="button" role="tab">Nilai Akhir SMART</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="ranking-tab" data-bs-toggle="tab" data-bs-target="#hasilPerangkingan" type="button" role="tab">Hasil Perangkingan</button>
                </li>
            </ul>
            <div class="tab-content mt-3" id="calculationTabsContent">
                {{-- 1. Nilai Profile Frame Tab --}}
                <div class="tab-pane fade" id="profileFrame" role="tabpanel">
                    <div class="table-responsive">
                        <table id="nilaiProfileFrameTable" class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Alternatif</th>
                                    <th>Foto Frame</th>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perhitungan['orderedRekomendasi'] as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    <td>
                                        @php
                                            // Handle different data structures
                                            $framePhoto = null;
                                            if (isset($frame['frame']) && is_object($frame['frame'])) {
                                                // Object structure (normal frame data)
                                                $framePhoto = $frame['frame']->frame_foto;
                                                $frameMerek = $frame['frame']->frame_merek ?? 'Frame';
                                            } elseif (isset($frame['frame']) && is_array($frame['frame'])) {
                                                // Array structure (processed data from processImageFiles)
                                                $framePhoto = $frame['frame']['frame_foto'] ?? null;
                                                $frameMerek = $frame['frame']['frame_merek'] ?? 'Frame';
                                            }
                                        @endphp
                                        
                                        @if($framePhoto)
                                            <img src="{{ asset('storage/'.$framePhoto) }}" 
                                                alt="{{ $frameMerek }}" 
                                                class="img-thumbnail" 
                                                style="max-width: 100px; max-height: 60px;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <div class="text-muted text-center" style="display: none;">Image Not Found</div>
                                        @else
                                            <div class="text-muted text-center">No Image</div>
                                        @endif
                                    </td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <td>
                                        @php
                                            // Get all subkriteria for this frame and criteria
                                            $frameSubkriterias = $frame['frame']->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id);
                                        @endphp
                                        
                                        @if($frameSubkriterias->count() > 0)
                                            @foreach($frameSubkriterias as $index => $frameSubkriteria)
                                                {{ $frameSubkriteria->subkriteria->subkriteria_nama }} ({{ $frameSubkriteria->subkriteria->subkriteria_bobot }})
                                                @if($index < $frameSubkriterias->count() - 1)
                                                    <br>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 2. Perhitungan GAP Tab --}}
                <div class="tab-pane fade" id="perhitunganGap" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Rumus Perhitungan GAP:</strong> Bobot Kriteria Frame - Bobot Kriteria Pelanggan
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong></strong> Untuk frame yang memiliki lebih dari 1 subkriteria, maka otomatis sistem akan mengambil gap/selisih yang sama dengan nol atau mendekati nol mendapatkan bobot gap terbesar
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="perhitunganGapTable" class="table table-striped table-hover">
                            <thead class="table-danger">
                                <tr>
                                    <th>Alternatif</th>
                                    <th>Foto Frame</th>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perhitungan['orderedRekomendasi'] as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    <td>
                                        @php
                                            $framePhoto = null;
                                            if (isset($frame['frame']) && is_object($frame['frame'])) {
                                                $framePhoto = $frame['frame']->frame_foto;
                                                $frameMerek = $frame['frame']->frame_merek ?? 'Frame';
                                            } elseif (isset($frame['frame']) && is_array($frame['frame'])) {
                                                $framePhoto = $frame['frame']['frame_foto'] ?? null;
                                                $frameMerek = $frame['frame']['frame_merek'] ?? 'Frame';
                                            }
                                        @endphp
                                        
                                        @if($framePhoto)
                                            <img src="{{ asset('storage/'.$framePhoto) }}" 
                                                alt="{{ $frameMerek }}" 
                                                class="img-thumbnail" 
                                                style="max-width: 100px; max-height: 60px;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <div class="text-muted text-center" style="display: none;">Image Not Found</div>
                                        @else
                                            <div class="text-muted text-center">No Image</div>
                                        @endif
                                    </td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <td>
                                        @php
                                            $kriteriaId = $kriteria->kriteria_id;
                                            $frameSubkriterias = $frame['frame']->frameSubkriterias->where('kriteria_id', $kriteriaId);
                                            $selectedFrameSubkriteria = null;
                                            
                                            // Find the subkriteria that was used in gap calculation
                                            foreach($frameSubkriterias as $fsk) {
                                                if(isset($frame['details'])) {
                                                    foreach($frame['details'] as $detail) {
                                                        if($detail['kriteria']->kriteria_id == $kriteriaId && 
                                                           $detail['frame_subkriteria']->subkriteria_id == $fsk->subkriteria->subkriteria_id) {
                                                            $selectedFrameSubkriteria = $fsk->subkriteria;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // If not found, try to get from first available
                                            if(!$selectedFrameSubkriteria && $frameSubkriterias->count() > 0) {
                                                $selectedFrameSubkriteria = $frameSubkriterias->first()->subkriteria;
                                            }
                                            
                                            // Get user subkriteria
                                            $userSubkriteria = null;
                                            if(isset($frame['details'])) {
                                                foreach($frame['details'] as $detail) {
                                                    if($detail['kriteria']->kriteria_id == $kriteriaId) {
                                                        $userSubkriteria = $detail['user_subkriteria'];
                                                        break;
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        @if($selectedFrameSubkriteria && $userSubkriteria)
                                            {{ $selectedFrameSubkriteria->subkriteria_bobot }} - {{ $userSubkriteria->subkriteria_bobot }} = {{ $frame['gap_values'][$kriteriaId] }}
                                        @else
                                            {{ $frame['gap_values'][$kriteriaId] }}
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 3. Konversi Nilai GAP Tab --}}
                <div class="tab-pane fade" id="konversiGap" role="tabpanel">
                    <div class="table-responsive">
                        <table id="konversiNilaiGapTable" class="table table-striped table-hover">
                            <thead class="table-success">
                                <tr>
                                    <th>Alternatif</th>
                                    <th>Foto Frame</th>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perhitungan['orderedRekomendasi'] as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td> 
                                    <td>
                                        @php
                                            $framePhoto = null;
                                            if (isset($frame['frame']) && is_object($frame['frame'])) {
                                                $framePhoto = $frame['frame']->frame_foto;
                                                $frameMerek = $frame['frame']->frame_merek ?? 'Frame';
                                            } elseif (isset($frame['frame']) && is_array($frame['frame'])) {
                                                $framePhoto = $frame['frame']['frame_foto'] ?? null;
                                                $frameMerek = $frame['frame']['frame_merek'] ?? 'Frame';
                                            }
                                        @endphp
                                        
                                        @if($framePhoto)
                                            <img src="{{ asset('storage/'.$framePhoto) }}" 
                                                alt="{{ $frameMerek }}" 
                                                class="img-thumbnail" 
                                                style="max-width: 100px; max-height: 60px;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <div class="text-muted text-center" style="display: none;">Image Not Found</div>
                                        @else
                                            <div class="text-muted text-center">No Image</div>
                                        @endif
                                    </td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <td>
                                        <span class="fw-bold">{{ $frame['gap_bobot'][$kriteria->kriteria_id] }}</span>
                                        <br>
                                        <small class="text-muted">(GAP: {{ $frame['gap_values'][$kriteria->kriteria_id] }})</small>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 4. Nilai Akhir SMART Tab --}}  
                <div class="tab-pane fade" id="nilaiAkhir" role="tabpanel">
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Perangkingan dengan Rumus SMART:</strong> Nilai konversi GAP X Normalisasi Kriteria, kemudian dijumlahkan untuk mendapatkan skor akhir.
                    </div>
                    <div class="table-responsive">
                        <table id="nilaiAkhirSMARTTable" class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Alternatif</th>
                                    <th>Foto Frame</th>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perhitungan['orderedRekomendasi'] as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    <td>
                                        @php
                                            $framePhoto = null;
                                            if (isset($frame['frame']) && is_object($frame['frame'])) {
                                                $framePhoto = $frame['frame']->frame_foto;
                                                $frameMerek = $frame['frame']->frame_merek ?? 'Frame';
                                            } elseif (isset($frame['frame']) && is_array($frame['frame'])) {
                                                $framePhoto = $frame['frame']['frame_foto'] ?? null;
                                                $frameMerek = $frame['frame']['frame_merek'] ?? 'Frame';
                                            }
                                        @endphp

                                        @if($framePhoto)
                                            <img src="{{ asset('storage/'.$framePhoto) }}" 
                                                alt="{{ $frameMerek }}" 
                                                class="img-thumbnail" 
                                                style="max-width: 100px; max-height: 60px;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <div class="text-muted text-center" style="display: none;">Image Not Found</div>
                                        @else
                                            <div class="text-muted text-center">No Image</div>
                                        @endif
                                    </td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <td>
                                        @php
                                            $normalizedWeight = $perhitungan['bobotKriteria'][$kriteria->kriteria_id];
                                            $gapWeight = $frame['gap_bobot'][$kriteria->kriteria_id];
                                            $result = round($normalizedWeight * $gapWeight, 4); // Bulatkan setiap hasil
                                        @endphp
                                                <div class="small">{{ $normalizedWeight }} × {{ $gapWeight }} =</div>
                                                <div class="fw-bold">
                                                    {{ number_format($result, 4) }}
                                                </div>
                                            </td>
                                            @endforeach
                                            <td><strong>{{ number_format($frame['score'], 4) }}</strong></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                {{-- 5. Hasil Perangkingan Tab (menggunakan rekomendasi yang sudah diurutkan berdasarkan skor) --}}
                <div class="tab-pane fade show active" id="hasilPerangkingan" role="tabpanel">
                    <div class="table-responsive">
                        <table id="hasilPerangkinganTable" class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Ranking</th>
                                    <th>Foto</th>
                                    <th>Merek</th>
                                    <th>Lokasi</th>
                                    <th>Kriteria</th>
                                    <th class="text-center">Skor Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekomendasi as $index => $frame)
                                <tr>
                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        @php
                                            $framePhoto = null;
                                            if (isset($frame['frame']) && is_object($frame['frame'])) {
                                                $framePhoto = $frame['frame']->frame_foto;
                                                $frameMerek = $frame['frame']->frame_merek ?? 'Frame';
                                            } elseif (isset($frame['frame']) && is_array($frame['frame'])) {
                                                $framePhoto = $frame['frame']['frame_foto'] ?? null;
                                                $frameMerek = $frame['frame']['frame_merek'] ?? 'Frame';
                                            }
                                        @endphp
                                        
                                        @if($framePhoto)
                                            <img src="{{ asset('storage/'.$framePhoto) }}" 
                                                alt="{{ $frameMerek }}" 
                                                class="img-thumbnail" 
                                                style="max-width: 180px; max-height: 90px;"
                                                onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                            <div class="text-muted text-center" style="display: none;">Image Not Found</div>
                                        @else
                                            <div class="text-muted text-center">No Image</div>
                                        @endif
                                    </td>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    <td>{{ $frame['frame']->frame_lokasi }}</td>
                                    <td>
                                        <small>
                                            @foreach($perhitungan['kriterias'] as $kriteria)
                                                @php
                                                    $frameSubkriterias = $frame['frame']->frameSubkriterias->where('kriteria_id', $kriteria->kriteria_id);
                                                    $hasManualValue = false;
                                                    $manualValues = [];
                                                    $checkboxValues = [];
                                                    
                                                    foreach($frameSubkriterias as $fs) {
                                                        if($fs->subkriteria) {
                                                            if($fs->manual_value !== null) {
                                                                // This is a manual value
                                                                $hasManualValue = true;
                                                                $manualValues[] = [
                                                                    'value' => $fs->manual_value,
                                                                    'name' => $fs->subkriteria->subkriteria_nama
                                                                ];
                                                            } else {
                                                                // This is a checkbox value
                                                                $checkboxValues[] = $fs->subkriteria->subkriteria_nama;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                
                                                <div class="mb-1">
                                                    <strong>{{ $kriteria->kriteria_nama }}:</strong>
                                                    @if(count($manualValues) > 0)
                                                        @foreach($manualValues as $manualItem)
                                                            {{ number_format($manualItem['value'], 2, ',', '.') }} ({{ $manualItem['name'] }}){{ !$loop->last ? ', ' : '' }}
                                                        @endforeach
                                                    @endif
                                                    
                                                    @if(count($checkboxValues) > 0)
                                                        {{ implode(', ', $checkboxValues) }}
                                                    @endif
                                                    
                                                    @if(count($manualValues) == 0 && count($checkboxValues) == 0)
                                                        <span class="text-muted">Tidak ada data</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">
                                            {{ number_format($frame['score'], 4) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/penilaian.js') }}"></script>
