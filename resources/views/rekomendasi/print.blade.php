<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekomendasi Frame - {{ $history->nama_pelanggan }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 15px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 5px;
        }
        h1 {
            font-size: 18px;
            margin: 0;
        }
        h2 {
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        h3 {
            font-size: 14px;
            margin-top: 12px;
            margin-bottom: 8px;
        }
        h4, h5 {
            font-size: 13px;
            margin-top: 10px;
            margin-bottom: 8px;
        }
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-left: 3px solid #007bff;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .customer-info {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .customer-info p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .recommendation-item {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .recommendation-item .frame-image {
            text-align: center;
            margin-bottom: 10px;
        }
        .recommendation-item .frame-image img {
            max-width: 150px;
            max-height: 100px;
        }
        .recommendation-details {
            display: flex;
            justify-content: space-between;
        }
        .detail-column {
            width: 48%;
        }
        .score-box {
            background-color: #f0f0f0;
            padding: 6px;
            text-align: center;
            font-weight: bold;
            margin-top: 8px;
            border-radius: 3px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            position: running(footer);
        }
        .page-break {
            page-break-before: always;
        }
        .date-printed {
            text-align: right;
            font-size: 10px;
            margin-bottom: 15px;
        }
        .rank-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 50%;
            background-color: #007bff;
            color: white;
            font-weight: bold;
            margin-right: 8px;
        }
        .alert {
            padding: 8px;
            background-color: #f8f9fa;
            border-left: 3px solid #28a745;
            margin-bottom: 15px;
        }
        .alert-info {
            background-color: #e8f4f8;
            border-left-color: #17a2b8;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: bold;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
        }
        .img-thumbnail {
            max-width: 100px;
            max-height: 60px;
        }
        @media print {
            body {
                padding: 0;
                margin: 10mm;
            }
            .no-print {
                display: none;
            }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- Uncomment and update with your actual logo path -->
        <img src="{{ asset('logokacamata.png') }}" alt="Logo" class="logo">
        <h1>LAPORAN HASIL REKOMENDASI FRAME KACAMATA</h1>
        <h1>Toko Kacamata Sidi Pingai</h1>
        <h1>Bukittinggi</h1>
    </div>

    <div class="date-printed">
        Dicetak pada: {{ now()->setTimezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
    </div>

    <!-- Customer Information Section -->
    <div class="section">
        <h2 class="section-title">Informasi Pelanggan</h2>
        <div class="customer-info">
            <p><strong>Nama:</strong> {{ $history->customer_name ?? ($history->customer->name ?? 'Unknown') }}</p>
            <p><strong>No. Hp:</strong> {{ $history->customer_phone ?? ($history->customer->phone ?? 'Unknown') }}</p>
            <p><strong>Alamat:</strong> {{ $history->customer_address ?? ($history->customer->address ?? 'Unknown') }}</p>
            <p><strong>Tanggal:</strong> {{ $history->created_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>
        </div>
    </div>

    <!-- Kriteria Section -->
    <div class="section">
        <h2 class="section-title">Kriteria yang Dipilih</h2>
        <table>
            <thead>
                <tr>
                    <th>Kriteria</th>
                    <th>Nilai yang Dipilih</th>
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

    <!-- Bobot Kriteria Section -->
    <div class="section">
        <h2 class="section-title">Bobot Kriteria</h2>
        <table>
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

    @php
        $perhitungan = $history->perhitungan_detail;
        $rekomendasi = $history->rekomendasi_data ?? [];
        $kriterias = $perhitungan['kriterias'] ?? [];
    @endphp

    <!-- Detail Perhitungan Section -->
    <div class="page-break"></div>
    {{-- <h2 class="section-title">Detail Perhitungan</h2> --}}

    {{-- <!-- 1. Nilai Profile Frame -->
    <div class="section">
        <h3>1. Nilai Profile Frame</h3>
        <table>
            <thead>
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

    <!-- 2. Perhitungan GAP -->
    <div class="section">
        <h3>2. Perhitungan GAP</h3>
        <table>
            <thead>
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

    <!-- 3. Konversi Nilai GAP -->
    <div class="section">
        <h3>3. Konversi Nilai GAP</h3>
        <table>
            <thead>
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

    <!-- 4. Nilai Akhir SMART -->
    <div class="section">
        <h3>4. Nilai Akhir SMART</h3>
        <table>
            <thead>
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
    </div> --}}

    <!-- 5. Hasil Perangkingan -->
    <div class="section">
        <h2 class="section-title">Hasil Perangkingan 10 Frame Teratas</h2>
        <table>
            <thead>
                <tr>
                    <th class="text-center">Ranking</th>
                    <th>Foto</th>
                    <th>Merek</th>
                    <th>Harga</th>
                    <th>Lokasi</th>
                    <th>Skor Akhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasi as $index => $frame)
                @if($index < 10)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        @if(isset($frame['frame']['frame_foto']))
                            <img src="{{ asset('storage/'.$frame['frame']['frame_foto']) }}" 
                                 alt="{{ $frame['frame']['frame_merek'] }}" 
                                 class="img-thumbnail">
                        @else
                            <div class="text-muted text-center">No Image</div>
                        @endif
                    </td>
                    <td>{{ $frame['frame']['frame_merek'] }}</td>
                    <td>Rp {{ number_format($frame['frame']['frame_harga'], 0, ',', '.') }}</td>
                    <td>{{ $frame['frame']['frame_lokasi'] }}</td>
                    <td class="text-center">
                        <span class="badge">
                            {{ number_format($frame['score'], 4) }}
                        </span>
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>


    <div class="footer">
        <p>Dokumen ini dihasilkan oleh Sistem Pendukung Keputusan Pemilihan Frame Kacamata</p>
        <p>&copy; {{ date('Y') }} - Toko Kacamata Sidi Pingai Bukittinggi</p>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print();" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Cetak Sekarang
        </button>
    </div>
</body>
</html>