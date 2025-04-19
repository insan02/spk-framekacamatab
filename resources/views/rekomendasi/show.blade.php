@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div data-success-message="{{ session('success') }}" style="display: none;"></div>
        @endif
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>Detail Riwayat Rekomendasi
            </h4>

            <div>
                <a href="{{ route('rekomendasi.index') }}" class="btn btn-sm btn-light">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
            </div>
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
                                    <strong>Nama:</strong> 
                                    <span>{{ $history->nama_pelanggan }}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>No HP:</strong> 
                                    <span>{{ $history->nohp_pelanggan }}</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Alamat:</strong> 
                                    <span>{{ $history->alamat_pelanggan }}</span>
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
                        <div class="table-responsive">
                            <table id="nilaiProfileFrameTable" class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Alternatif</th>
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasi as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        @foreach($kriterias as $kriteria)
                                        @php
                                            $detail = collect($frame['details'])->firstWhere('kriteria.kriteria_id', $kriteria['kriteria_id']);
                                            $subkriteria = $detail['frame_subkriteria'];
                                        @endphp
                                        <td>{{ $subkriteria['subkriteria_nama'] }} ({{ $subkriteria['subkriteria_bobot'] }})</td>
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
                        <div class="table-responsive">
                            <table id="perhitunganGapTable" class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Alternatif</th>
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasi as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        @foreach($kriterias as $kriteria)
                                        <td>{{ $frame['gap_values'][$kriteria['kriteria_id']] }}</td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- 3. Konversi Nilai GAP --}}
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>3. Konversi Nilai GAP</strong></h5>
                        <div class="table-responsive">
                            <table id="konversiNilaiGapTable" class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Alternatif</th>
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasi as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        @foreach($kriterias as $kriteria)
                                        <td>{{ $frame['gap_bobot'][$kriteria['kriteria_id']] }}</td>
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
                        <div class="table-responsive">
                            <table id="nilaiAkhirSMARTTable" class="table table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Alternatif</th>
                                        @foreach($kriterias as $kriteria)
                                        <th>{{ $kriteria['kriteria_nama'] }}</th>
                                        @endforeach
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasi as $frame)
                                    <tr>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        @foreach($kriterias as $kriteria)
                                        <td>
                                            {{ number_format(
                                                $perhitungan['bobotKriteria'][$kriteria['kriteria_id']] * 
                                                $frame['gap_bobot'][$kriteria['kriteria_id']], 
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

                    {{-- 5. Hasil Perangkingan --}}
                    <div class="mb-4">
                        <h5 class="mb-3"><strong>5. Hasil Perangkingan</strong></h5>
                        <div class="table-responsive">
                            <table id="hasilPerangkinganTable" class="table table-hover table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th class="text-center">Ranking</th>
                                        <th>Foto</th>
                                        <th>Merek</th>
                                        <th>Harga</th>
                                        <th>Lokasi</th>
                                        <th>Kriteria Utama</th>
                                        <th class="text-center">Skor Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rekomendasi as $index => $frame)
                                    <tr>
                                        <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                        <td>
                                            @if(isset($frame['frame']['frame_foto']))
                                                <img src="{{ asset('storage/'.$frame['frame']['frame_foto']) }}" 
                                                     alt="{{ $frame['frame']['frame_merek'] }}" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 180px; max-height: 90px;">
                                            @else
                                                <div class="text-muted text-center">No Image</div>
                                            @endif
                                        </td>
                                        <td>{{ $frame['frame']['frame_merek'] }}</td>
                                        <td>Rp {{ number_format($frame['frame']['frame_harga'], 0, ',', '.') }}</td>
                                        <td>{{ $frame['frame']['frame_lokasi'] }}</td>
                                        <td>
                                            <small>
                                                @foreach($frame['details'] as $detail)
                                                    {{ $detail['kriteria']['kriteria_nama'] }}: 
                                                    {{ $detail['frame_subkriteria']['subkriteria_nama'] }}<br>
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
