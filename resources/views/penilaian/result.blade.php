<div class="row mt-4">
    <div class="col-md-12">
        <h4>Data Pelanggan</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Keterangan</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Nama</strong></td>
                        <td>{{ $nama_pelanggan }}</td>
                    </tr>
                    <tr>
                        <td><strong>No HP</strong></td>
                        <td>{{ $nohp_pelanggan }}</td>
                    </tr>
                    <tr>
                        <td><strong>Alamat</strong></td>
                        <td>{{ $alamat_pelanggan }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-12">
        <h4 class="mb-4">Detail Perhitungan</h4>

        <!-- 1. Nilai Profile (Raw Values) -->
        <div class="mb-5">
            <h5 class="mb-3">1. Nilai Profile Frame</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            @foreach($perhitungan['kriterias'] as $kriteria)
                            <th>{{ $kriteria->kriteria_nama }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perhitungan['rekomendasi'] as $frame)
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

        <!-- 2. Nilai Profile User -->
        <div class="mb-5">
            <h5 class="mb-3">2. Profil Ideal Pengguna</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
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

        <!-- 3. Bobot Kriteria -->
        <div class="mb-5">
            <h5 class="mb-3">3. Pembobotan Kriteria</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Kriteria</th>
                            <th>Bobot (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perhitungan['kriterias'] as $kriteria)
                        <tr>
                            <td>{{ $kriteria->kriteria_nama }}</td>
                            <td>{{ $perhitungan['bobotKriteriaUser'][$kriteria->kriteria_id] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Nilai GAP -->
        <div class="mb-5">
            <h5 class="mb-3">4. Perhitungan GAP</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            @foreach($perhitungan['kriterias'] as $kriteria)
                            <th>{{ $kriteria->kriteria_nama }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perhitungan['rekomendasi'] as $frame)
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

        <!-- 5. Konversi Bobot GAP -->
        <div class="mb-5">
            <h5 class="mb-3">5. Konversi Nilai GAP</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            @foreach($perhitungan['kriterias'] as $kriteria)
                            <th>{{ $kriteria->kriteria_nama }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perhitungan['rekomendasi'] as $frame)
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

        <!-- 6. Perhitungan SMART -->
        <div class="mb-5">
            <h5 class="mb-3">6. Nilai Akhir (Metode SMART)</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Alternatif</th>
                            @foreach($perhitungan['kriterias'] as $kriteria)
                            <th>{{ $kriteria->kriteria_nama }}</th>
                            @endforeach
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perhitungan['rekomendasi'] as $frame)
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

        <!-- 7. Hasil Rangking -->
        <div class="mb-5">
            <h5 class="mb-3">7. Hasil Perangkingan</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Ranking</th>
                            <th>Frame</th>
                            <th>Skor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perhitungan['rekomendasi'] as $index => $frame)
                        <tr class="{{ $index < 5 ? 'table-primary' : '' }}">
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $frame['frame']->frame_merek }}</td>
                            <td>{{ number_format($frame['score'], 4) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>