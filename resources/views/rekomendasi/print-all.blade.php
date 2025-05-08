<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekomendasi Frame Kacamata</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .report-subtitle {
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .report-info {
            margin-bottom: 15px;
        }
        h1 {
            font-size: 18px;
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            text-align: right;
            page-break-inside: avoid;
        }

        .footerr {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            position: running(footer);
        }
        
        .signature {
            margin-top: 50px;
        }

        .logo {
            max-width: 100px;
            margin-bottom: 5px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="no-print mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
            <a href="{{ route('rekomendasi.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        
        <div class="report-header">
            <img src="{{ asset('logokacamata.png') }}" alt="Logo" class="logo">
            <h1>LAPORAN HASIL REKOMENDASI FRAME KACAMATA</h1>
            <h1>Toko Kacamata Sidi Pingai</h1>
            <h1>Bukittinggi</h1>
            <div class="report-info">
                @if($startDate && $endDate)
                    Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                @elseif($startDate)
                    Periode: Dari {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                @elseif($endDate)
                    Periode: Sampai {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                @else
                    Periode: Seluruh Data
                @endif
            </div>
        </div>
        
        <div class="report-content">
            <div class="report-summary mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5><strong>Total Rekomendasi:</strong> {{ count($histories) }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h5>Daftar Rekomendasi:</h5>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Pelanggan</th>
                        <th>No HP</th>
                        <th>Kriteria Terpilih</th>
                        <th>Rekomendasi Teratas</th>
                        <!-- <th>Skor</th> -->
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $index => $history)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $history->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $history->customer_name ?? ($history->customer->name ?? 'Unknown') }}</td>
                        <td>{{ $history->customer_phone ?? ($history->customer->phone ?? 'Unknown') }}</td>
                        <td>
                            @php
                                $kriteriaTerpilih = $history->kriteria_dipilih ?? [];
                            @endphp
                            @foreach($kriteriaTerpilih as $kriteria => $subkriteria)
                                <small>{{ $kriteria }}: {{ $subkriteria }}<br></small>
                            @endforeach
                        </td>
                        <td>
                            @php
                                $rekomendasi = $history->rekomendasi_data ?? [];
                                $topRekomendasi = $rekomendasi[0] ?? null;
                            @endphp
                            @if($topRekomendasi)
                                <div class="d-flex flex-column align-items-start">
                                    @if(isset($topRekomendasi['frame']['frame_foto']))
                                        <img src="{{ asset('storage/'.$topRekomendasi['frame']['frame_foto']) }}" 
                                             alt="{{ $topRekomendasi['frame']['frame_merek'] }}" 
                                             class="img-thumbnail mb-2" 
                                             style="max-width: 200px; max-height: 100px;">
                                    @endif
                                    <div class="small text-secondary">
                                        <div><span class="text-dark">Merek:</span> {{ $topRekomendasi['frame']['frame_merek'] ?? '-' }}</div>
                                        <div><span class="text-dark">Lokasi:</span> {{ $topRekomendasi['frame']['frame_lokasi'] ?? '-' }}</div>
                                        <div><span class="text-dark">Skor:</span> {{ number_format($topRekomendasi['score'] ?? 0, 4) }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">Tidak ada rekomendasi</span>
                            @endif
                        </td>
                        
                        <!-- <td>
                            @if($topRekomendasi)
                                {{ number_format($topRekomendasi['score'] ?? 0, 4) }}
                            @else
                                -
                            @endif
                        </td> -->
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Dicetak pada: {{ now()->format('d/m/Y') }}</p>
            
            <div class="signature">
                <p>Mengetahui,</p>
                <br><br><br>
                <p>_____________________</p>
                <p>Owner</p>
            </div>
        </div>

        <div class="footerr">
            <p>Dokumen ini dihasilkan oleh Sistem Pendukung Keputusan Pemilihan Frame Kacamata</p>
            <p>&copy; {{ date('Y') }} - Toko Kacamata Sidi Pingai Bukittinggi</p>
        </div>
        
    </div>
    
    <script>
        window.onload = function() {
            // Auto print if not viewing in preview mode
            const params = new URLSearchParams(window.location.search);
            if (params.get('preview') !== 'true') {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        };
    </script>
</body>
</html>