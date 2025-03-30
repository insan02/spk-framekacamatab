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
                                <span>{{ $nama_pelanggan }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <strong class="me-2">No HP:</strong> 
                                <span>{{ $nohp_pelanggan }}</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <strong class="me-2">Alamat:</strong> 
                                <span>{{ $alamat_pelanggan }}</span>
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
                                            {{ number_format($perhitungan['bobotKriteria'][$kriteria->kriteria_id], 4) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="alert alert-info">
                                <strong>Total Bobot Normalisasi:</strong> 
                                {{ number_format(array_sum($perhitungan['bobotKriteria']), 4) }}
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
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekomendasi as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    @php
                                        $detail = collect($frame['details'])->firstWhere('kriteria.kriteria_id', $kriteria->kriteria_id);
                                        $subkriteria = $detail['frame_subkriteria'];
                                    @endphp
                                    <td>{{ $subkriteria->subkriteria_nama }} ({{ $subkriteria->subkriteria_bobot }})</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 2. Perhitungan GAP Tab --}}
                <div class="tab-pane fade" id="perhitunganGap" role="tabpanel">
                    <div class="table-responsive">
                        <table id="perhitunganGapTable" class="table table-striped table-hover">
                            <thead class="table-danger">
                                <tr>
                                    <th>Alternatif</th>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekomendasi as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <td>{{ $frame['gap_values'][$kriteria->kriteria_id] }}</td>
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
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekomendasi as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <td>{{ $frame['gap_bobot'][$kriteria->kriteria_id] }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 4. Nilai Akhir SMART Tab --}}
                <div class="tab-pane fade" id="nilaiAkhir" role="tabpanel">
                    <div class="table-responsive">
                        <table id="nilaiAkhirSMARTTable" class="table table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Alternatif</th>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <th>{{ $kriteria->kriteria_nama }}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekomendasi as $frame)
                                <tr>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    @foreach($perhitungan['kriterias'] as $kriteria)
                                    <td>
                                        {{ number_format(
                                            $perhitungan['bobotKriteria'][$kriteria->kriteria_id] * 
                                            $frame['gap_bobot'][$kriteria->kriteria_id], 
                                            4
                                        ) }}
                                    </td>
                                    @endforeach
                                    <td><strong>{{ $frame['score'] }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 5. Hasil Perangkingan Tab --}}
                <div class="tab-pane fade show active" id="hasilPerangkingan" role="tabpanel">
                    <div class="table-responsive">
                        <table id="hasilPerangkinganTable" class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">Ranking</th>
                                    <th>Foto</th>
                                    <th>Merek</th>
                                    <th>Harga</th>
                                    <th>Kriteria Utama</th>
                                    <th class="text-center">Skor Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rekomendasi as $index => $frame)
                                <tr>
                                    <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        @if($frame['frame']->frame_foto)
                                            <img src="{{ asset('storage/'.$frame['frame']->frame_foto) }}" 
                                                 alt="{{ $frame['frame']->frame_merek }}" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 180px; max-height: 90px;">
                                        @else
                                            <div class="text-muted text-center">No Image</div>
                                        @endif
                                    </td>
                                    <td>{{ $frame['frame']->frame_merek }}</td>
                                    <td>Rp {{ number_format($frame['frame']->frame_harga, 0, ',', '.') }}</td>
                                    <td>
                                        <small>
                                            @foreach($frame['details'] as $detail)
                                                {{ $detail['kriteria']->kriteria_nama }}: 
                                                {{ $detail['frame_subkriteria']->subkriteria_nama }}<br>
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