@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4>Hasil Rekomendasi untuk {{ $penilaian->nama_pelanggan }}</h4>
        </div>
        <div class="card-body">

            <div class="mb-4">
                <h5>Detail Perhitungan Profile Matching & SMART:</h5>
                
                <!-- 1. Nilai Profile (Raw Values) -->
                <div class="mb-5">
                    <h5 class="mb-3">1. Nilai Profile (Raw Values)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Alternatif</th>
                                    @foreach(array_keys(reset($detailPerhitungan['rawValues'])) as $kriteria)
                                    <th>{{ $kriteria }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailPerhitungan['rawValues'] as $frame => $nilai)
                                <tr>
                                    <td>{{ $frame }}</td>
                                    @foreach($nilai as $k => $v)
                                    <td>{{ $v['nama'] }} ({{ number_format($v['bobot'], 2) }})</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Nilai Profile User -->
                <div class="mb-5">
                    <h5 class="mb-3">2. Nilai Profile User (Profil Ideal)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Kriteria</th>
                                    <th>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailPerhitungan['userValues'] as $kriteria => $nilai)
                                <tr>
                                    <td>{{ $kriteria }}</td>
                                    <td>{{ $nilai['nama'] }} ({{ number_format($nilai['bobot'], 2) }})</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. Bobot Kriteria -->
                <div class="mb-5">
                    <h5 class="mb-3">3. Bobot Kriteria</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Kriteria</th>
                                    <th>Bobot (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailPerhitungan['bobotKriteria'] as $kriteria => $bobot)
                                <tr>
                                    <td>{{ $kriteria }}</td>
                                    <td>{{ number_format($bobot, 2) }}%</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. GAP Values -->
                <div class="mb-5">
                    <h5 class="mb-3">4. Nilai GAP (Selisih)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Alternatif</th>
                                    @foreach(array_keys(reset($detailPerhitungan['gapValues'])) as $kriteria)
                                    <th>{{ $kriteria }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailPerhitungan['gapValues'] as $frame => $nilai)
                                <tr>
                                    <td>{{ $frame }}</td>
                                    @foreach($nilai as $k => $v)
                                    <td>{{ number_format($v, 2) }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 5. GAP Bobot Values -->
                <div class="mb-5">
                    <h5 class="mb-3">5. Konversi GAP ke Nilai Bobot</h5>
                    <!-- Tabel Bobot GAP -->
                    <div class="mt-3">
                        <h6>Tabel Bobot GAP:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" style="max-width: 500px;">
                                <thead>
                                    <tr>
                                        <th>Selisih (GAP)</th>
                                        <th>Bobot Nilai</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>0</td>
                                        <td>5.0</td>
                                        <td>Tidak ada selisih</td>
                                    </tr>
                                    <tr>
                                        <td>1</td>
                                        <td>4.5</td>
                                        <td>Kelebihan 1 tingkat</td>
                                    </tr>
                                    <tr>
                                        <td>-1</td>
                                        <td>4.0</td>
                                        <td>Kekurangan 1 tingkat</td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>3.5</td>
                                        <td>Kelebihan 2 tingkat</td>
                                    </tr>
                                    <tr>
                                        <td>-2</td>
                                        <td>3.0</td>
                                        <td>Kekurangan 2 tingkat</td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>2.5</td>
                                        <td>Kelebihan 3 tingkat</td>
                                    </tr>
                                    <tr>
                                        <td>-3</td>
                                        <td>2.0</td>
                                        <td>Kekurangan 3 tingkat</td>
                                    </tr>
                                    <tr>
                                        <td>4</td>
                                        <td>1.5</td>
                                        <td>Kelebihan 4 tingkat</td>
                                    </tr>
                                    <tr>
                                        <td>-4</td>
                                        <td>1.0</td>
                                        <td>Kekurangan 4 tingkat</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <h6>Tabel Konversi Bobot GAP:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Alternatif</th>
                                    @foreach(array_keys(reset($detailPerhitungan['gapBobot'])) as $kriteria)
                                    <th>{{ $kriteria }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailPerhitungan['gapBobot'] as $frame => $nilai)
                                <tr>
                                    <td>{{ $frame }}</td>
                                    @foreach($nilai as $k => $v)
                                    <td>{{ number_format($v, 2) }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 6. Nilai Akhir dengan Metode SMART -->
                <div class="mb-5">
                    <h5 class="mb-3">6. Nilai Terbobot (Metode SMART)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Alternatif</th>
                                    @foreach(array_keys(reset($detailPerhitungan['weightedScores'])) as $kriteria)
                                    <th>{{ $kriteria }}</th>
                                    @endforeach
                                    <th>Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailPerhitungan['weightedScores'] as $frame => $nilai)
                                <tr>
                                    <td>{{ $frame }}</td>
                                    @foreach($nilai as $k => $v)
                                    <td>{{ number_format($v, 4) }}</td>
                                    @endforeach
                                    <td><strong>{{ number_format($detailPerhitungan['finalScores'][$frame], 4) }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 7. Hasil Perangkingan -->
                <div class="mb-5">
                    <h5 class="mb-3">7. Hasil Perangkingan</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Ranking</th>
                                    <th>Alternatif</th>
                                    <th>Nilai Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rank = 1; @endphp
                                @foreach($detailPerhitungan['sortedScores'] as $frame => $nilai)
                                <tr class="{{ $rank <= 5 ? 'table-primary' :''}}">
                                    <td>{{ $rank }}</td>
                                    <td>{{ $frame }}</td>
                                    <td>{{ number_format($nilai, 4) }}</td>
                                </tr>
                                @php $rank++; @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="mt-4">
                <a href="{{ route('rekomendasi.index') }}" class="btn btn-secondary">Kembali</a>
                <a href="{{ route('rekomendasi.print', $penilaian->penilaian_id) }}" class="btn btn-primary" target="_blank">
                    <i class="fa fa-print"></i> Cetak Rekomendasi
                </a>
            </div>
        </div>
    </div>
</div>
@endsection