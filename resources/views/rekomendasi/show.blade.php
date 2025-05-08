@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <a href="{{ route('rekomendasi.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    @if(session('success'))
        <div data-success-message="{{ session('success') }}" style="display: none;"></div>
        @endif
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>Detail Riwayat Penilaian
            </h4>
        </div>
        <div class="card-body">
            {{-- Customer Information Section --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>Data Pelanggan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <strong class="me-2">Nama:</strong> 
                                        <span>{{ $history->customer_name ?? ($history->customer->name ?? 'Unknown') }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <strong class="me-2">No HP:</strong> 
                                        <span>{{ $history->customer_phone ?? ($history->customer->phone ?? 'Unknown') }}</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <strong class="me-2">Alamat:</strong> 
                                        <span>{{ $history->customer_address ?? ($history->customer->address ?? 'Unknown') }}</span>
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
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list-alt me-2"></i>Kriteria Pilihan
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kriteria</th>
                                        <th>Subkriteria Dipilih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($history->kriteria_dipilih as $kriteria => $subkriteria)
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

            {{-- Bobot Kriteria --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-weight me-2"></i>Bobot Kriteria
                            </h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kriteria</th>
                                        <th>Bobot Awal</th>
                                        <th>Bobot Normalisasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $perhitungan = $history->perhitungan_detail;
                                        $kriterias = $perhitungan['kriterias'] ?? [];
                                        $bobotKriteriaUser = $history->bobot_kriteria ?? [];
                                        $bobotKriteria = $perhitungan['bobotKriteria'] ?? [];
                                    @endphp
                                    @foreach($kriterias as $kriteria)
                                    <tr>
                                        <td>{{ $kriteria['kriteria_nama'] }}</td>
                                        <td>{{ $bobotKriteriaUser[$kriteria['kriteria_id']] }}</td>
                                        <td>{{ number_format($bobotKriteria[$kriteria['kriteria_id']], 4) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="alert alert-info">
                                <strong>Total Bobot Normalisasi:</strong> 
                                {{ number_format(array_sum($bobotKriteria), 4) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calculation Details --}}
            @php
                $perhitungan = $history->perhitungan_detail;
                $rekomendasi = $history->rekomendasi_data ?? [];
                $kriterias = $perhitungan['kriterias'] ?? [];
                
                // Sort rekomendasi by frame_id for tables 1-4
                $rekomendasiByFrameId = collect($rekomendasi)->sortBy(function($frame) {
                    return $frame['frame']['frame_id'];
                })->values()->all();
                
                // Keep original sorting by score for table 5
                $rekomendasiByScore = $rekomendasi;
            @endphp

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>Detail Perhitungan
                    </h4>
                </div>
                <div class="card-body">
                    {{-- 1. Nilai Profile Frame --}}
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>1. Nilai Profile Frame</strong></h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Profile Frame:</strong> Menampilkan semua nilai subkriteria yang dimiliki oleh masing-masing frame
                        </div>
                        <div class="table-responsive">
                            <table id="nilaiProfileFrameTable" class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Alternatif</th>
                                        <th>Foto Frame</th>
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasiByFrameId as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        <td>
                                            @if(isset($frame['frame']['frame_foto']) && $frame['frame']['frame_foto'])
                                                <img src="{{ asset('storage/'.$frame['frame']['frame_foto']) }}" 
                                                     alt="{{ $frame['frame']['frame_merek'] }}" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 100px; max-height: 60px;">
                                            @else
                                                <div class="text-muted text-center">No Image</div>
                                            @endif
                                        </td>  
                                        @foreach($kriterias as $kriteria)
                                        <td>
                                            @php
                                                $kriteriaId = $kriteria['kriteria_id'];
                                                $subkriteriaList = [];
                                                
                                                // Check in all possible places where subkriteria might be stored
                                                
                                                // 1. Check in frameSubkriterias (direct property from original data)
                                                if (isset($frame['frame']['frameSubkriterias']) && is_array($frame['frame']['frameSubkriterias'])) {
                                                    foreach ($frame['frame']['frameSubkriterias'] as $fsk) {
                                                        if ($fsk['kriteria_id'] == $kriteriaId && isset($fsk['subkriteria'])) {
                                                            $subkriteriaList[] = [
                                                                'nama' => $fsk['subkriteria']['subkriteria_nama'],
                                                                'bobot' => $fsk['subkriteria']['subkriteria_bobot']
                                                            ];
                                                        }
                                                    }
                                                }
                                                
                                                // 2. Check in frame_subkriterias (alternate naming)
                                                if (isset($frame['frame']['frame_subkriterias']) && is_array($frame['frame']['frame_subkriterias'])) {
                                                    foreach ($frame['frame']['frame_subkriterias'] as $fsk) {
                                                        if ($fsk['kriteria_id'] == $kriteriaId) {
                                                            $subkriteriaList[] = [
                                                                'nama' => $fsk['subkriteria']['subkriteria_nama'] ?? $fsk['subkriteria_nama'] ?? '',
                                                                'bobot' => $fsk['subkriteria']['subkriteria_bobot'] ?? $fsk['subkriteria_bobot'] ?? ''
                                                            ];
                                                        }
                                                    }
                                                }
                                                
                                                // 3. Check in all_subkriteria
                                                if (isset($frame['frame']['all_subkriteria']) && is_array($frame['frame']['all_subkriteria'])) {
                                                    foreach ($frame['frame']['all_subkriteria'] as $subk) {
                                                        if ($subk['kriteria_id'] == $kriteriaId) {
                                                            $subkriteriaList[] = [
                                                                'nama' => $subk['subkriteria_nama'],
                                                                'bobot' => $subk['subkriteria_bobot']
                                                            ];
                                                        }
                                                    }
                                                }
                                                
                                                // 4. Check in details array as a fallback
                                                if (isset($frame['details'])) {
                                                    foreach ($frame['details'] as $detail) {
                                                        if ($detail['kriteria']['kriteria_id'] == $kriteriaId) {
                                                            $subk = $detail['frame_subkriteria'];
                                                            $subkriteriaList[] = [
                                                                'nama' => $subk['subkriteria_nama'],
                                                                'bobot' => $subk['subkriteria_bobot']
                                                            ];
                                                        }
                                                    }
                                                }
                                                
                                                // Remove duplicates by nama
                                                $uniqueList = [];
                                                foreach ($subkriteriaList as $item) {
                                                    $uniqueList[$item['nama']] = $item;
                                                }
                                                $subkriteriaList = array_values($uniqueList);
                                            @endphp
                                            
                                            @if(count($subkriteriaList) > 0)
                                                @foreach($subkriteriaList as $index => $subk)
                                                    {{ $subk['nama'] }} ({{ $subk['bobot'] }})
                                                    @if($index < count($subkriteriaList) - 1)
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

                    {{-- 2. Perhitungan GAP --}}
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>2. Perhitungan GAP</strong></h5>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Rumus Perhitungan GAP:</strong> Nilai Subkriteria Frame - Nilai Subkriteria Pelanggan
                                </div>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong></strong> Untuk frame yang memiliki lebih dari 1 subkriteria, maka otomatis sistem akan mengambil gap/selisih terkecil untuk mendapatkan bobot gap terbesar
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="perhitunganGapTable" class="table table-striped table-hover">
                                <thead class="table-danger">
                                    <tr>
                                        <th>Alternatif</th>
                                        <th>Foto Frame</th>
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasiByFrameId as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        <td>
                                            @if(isset($frame['frame']['frame_foto']) && $frame['frame']['frame_foto'])
                                                <img src="{{ asset('storage/'.$frame['frame']['frame_foto']) }}" 
                                                     alt="{{ $frame['frame']['frame_merek'] }}" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 100px; max-height: 60px;">
                                            @else
                                                <div class="text-muted text-center">No Image</div>
                                            @endif
                                        </td>
                                        @foreach($kriterias as $kriteria)
                                        @php
                                            $kriteriaId = $kriteria['kriteria_id'];
                                            $frameSubkriteria = null;
                                            $userSubkriteria = null;
                                            
                                            // Find the subkriteria used in gap calculation
                                            foreach($frame['details'] as $detail) {
                                                if($detail['kriteria']['kriteria_id'] == $kriteriaId) {
                                                    $frameSubkriteria = $detail['frame_subkriteria'];
                                                    $userSubkriteria = $detail['user_subkriteria'];
                                                    break;
                                                }
                                            }
                                        @endphp
                                        <td>
                                            @if($frameSubkriteria && $userSubkriteria)
                                                {{ $frameSubkriteria['subkriteria_bobot'] }} - {{ $userSubkriteria['subkriteria_bobot'] }} = {{ $frame['gap_values'][$kriteriaId] }}
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

                    {{-- 3. Konversi Nilai GAP --}}
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>3. Pembobotan Nilai GAP</strong></h5>
                        <div class="table-responsive">
                            <table id="konversiNilaiGapTable" class="table table-striped table-hover">
                                <thead class="table-success">
                                    <tr>
                                        <th>Alternatif</th>
                                        <th>Foto Frame</th>
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasiByFrameId as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        <td>
                                            @if(isset($frame['frame']['frame_foto']) && $frame['frame']['frame_foto'])
                                                <img src="{{ asset('storage/'.$frame['frame']['frame_foto']) }}" 
                                                     alt="{{ $frame['frame']['frame_merek'] }}" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 100px; max-height: 60px;">
                                            @else
                                                <div class="text-muted text-center">No Image</div>
                                            @endif
                                        </td>
                                        @foreach($kriterias as $kriteria)
                                        <td>
                                            <span class="fw-bold">{{ $frame['gap_bobot'][$kriteria['kriteria_id']] }}</span>
                                            <br>
                                            <small class="text-muted">(GAP: {{ $frame['gap_values'][$kriteria['kriteria_id']] }})</small>
                                        </td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 4. Nilai Akhir SMART --}}
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>4. Nilai Akhir SMART</strong></h5>
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
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasiByFrameId as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        <td>
                                            @if(isset($frame['frame']['frame_foto']) && $frame['frame']['frame_foto'])
                                                <img src="{{ asset('storage/'.$frame['frame']['frame_foto']) }}" 
                                                     alt="{{ $frame['frame']['frame_merek'] }}" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 100px; max-height: 60px;">
                                            @else
                                                <div class="text-muted text-center">No Image</div>
                                            @endif
                                        </td>
                                        @foreach($kriterias as $kriteria)
                                        <td>
                                            <div class="small">{{ $perhitungan['bobotKriteria'][$kriteria['kriteria_id']] }} × {{ $frame['gap_bobot'][$kriteria['kriteria_id']] }} =</div>
                                            <div class="fw-bold">
                                                {{ number_format(
                                                    $perhitungan['bobotKriteria'][$kriteria['kriteria_id']] * 
                                                    $frame['gap_bobot'][$kriteria['kriteria_id']], 
                                                    4
                                                ) }}
                                            </div>
                                        </td>
                                        @endforeach
                                        <td><strong>{{ $frame['score'] }}</strong></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 5. Hasil Perangkingan --}}
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>5. Hasil Perangkingan</strong></h5>
                        <div class="table-responsive">
                            <table id="hasilPerangkinganTable" class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Ranking</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Lokasi</th>
                                        <th>Kriteria Utama</th>
                                        <th class="text-center">Skor Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasiByScore as $index => $frame)
                                    <tr>
                                        <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                        <td>
                                            @if(isset($frame['frame']['frame_foto']) && $frame['frame']['frame_foto'])
                                                <img src="{{ asset('storage/'.$frame['frame']['frame_foto']) }}" 
                                                     alt="{{ $frame['frame']['frame_merek'] }}" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 180px; max-height: 90px;">
                                            @else
                                                <div class="text-muted text-center">No Image</div>
                                            @endif
                                        </td>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        <td>{{ $frame['frame']['frame_lokasi'] }}</td>
                                        <td>
                                            <small>
                                                @foreach($kriterias as $kriteria)
                                                    <strong>{{ $kriteria['kriteria_nama'] }}:</strong><br>
                                                    @php
                                                        $kriteriaId = $kriteria['kriteria_id'];
                                                        $subkriteriaList = [];
                                                        
                                                        // Check all possible places where subkriteria might be stored
                                                        // Same logic as above to find all subkriteria for this frame and kriteria
                                                        
                                                        // 1. Check in frameSubkriterias
                                                        if (isset($frame['frame']['frameSubkriterias']) && is_array($frame['frame']['frameSubkriterias'])) {
                                                            foreach ($frame['frame']['frameSubkriterias'] as $fsk) {
                                                                if ($fsk['kriteria_id'] == $kriteriaId && isset($fsk['subkriteria'])) {
                                                                    $subkriteriaList[] = $fsk['subkriteria']['subkriteria_nama'];
                                                                }
                                                            }
                                                        }
                                                        
                                                        // 2. Check in frame_subkriterias
                                                        if (isset($frame['frame']['frame_subkriterias']) && is_array($frame['frame']['frame_subkriterias'])) {
                                                            foreach ($frame['frame']['frame_subkriterias'] as $fsk) {
                                                                if ($fsk['kriteria_id'] == $kriteriaId) {
                                                                    $subkriteriaList[] = $fsk['subkriteria']['subkriteria_nama'] ?? $fsk['subkriteria_nama'] ?? '';
                                                                }
                                                            }
                                                        }
                                                        
                                                        // 3. Check in all_subkriteria
                                                        if (isset($frame['frame']['all_subkriteria']) && is_array($frame['frame']['all_subkriteria'])) {
                                                            foreach ($frame['frame']['all_subkriteria'] as $subk) {
                                                                if ($subk['kriteria_id'] == $kriteriaId) {
                                                                    $subkriteriaList[] = $subk['subkriteria_nama'];
                                                                }
                                                            }
                                                        }
                                                        
                                                        // 4. Check in details array
                                                        if (isset($frame['details'])) {
                                                            foreach ($frame['details'] as $detail) {
                                                                if ($detail['kriteria']['kriteria_id'] == $kriteriaId) {
                                                                    $subkriteriaList[] = $detail['frame_subkriteria']['subkriteria_nama'];
                                                                }
                                                            }
                                                        }
                                                        
                                                        // Remove duplicates
                                                        $subkriteriaList = array_unique($subkriteriaList);
                                                    @endphp
                                                    
                                                    @if(count($subkriteriaList) > 0)
                                                        <span class="ps-2">
                                                            {{ implode(', ', $subkriteriaList) }}
                                                        </span>
                                                    @else
                                                        <span class="ps-2 text-muted">- Tidak ada data</span>
                                                    @endif
                                                    <br>
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
</div>


<script src="{{ asset('js/rekomendasii.js') }}"></script>
@endsection