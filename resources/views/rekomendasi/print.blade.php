<!DOCTYPE html>
<html>
<head>
    <title>Rekomendasi Frame - {{ $penilaian->nama_pelanggan }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .header { text-align: center; margin-bottom: 20px; }
        .customer-info { margin-bottom: 20px; }
        .criteria-info { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rekomendasi Frame Kacamata</h1>
    </div>

    <div class="customer-info">
        <h2>Informasi Pelanggan</h2>
        <p><strong>Nama:</strong> {{ $penilaian->nama_pelanggan }}</p>
        <p><strong>No HP:</strong> {{ $penilaian->nohp_pelanggan }}</p>
        <p><strong>Alamat:</strong> {{ $penilaian->alamat_pelanggan }}</p>
        <p><strong>Tanggal Penilaian:</strong> {{ $penilaian->tgl_penilaian->format('d/m/Y H:i') }}</p>
    </div>

    <div class="criteria-info">
        <h2>Kriteria Terpilih</h2>
        <ul>
            @foreach($penilaian->detailPenilaians as $detail)
                <li>
                    <strong>{{ $detail->kriteria->kriteria_nama }}:</strong> 
                    {{ $detail->subkriteria->subkriteria_nama }}
                </li>
            @endforeach
        </ul>
    </div>

    <div class="recommendations">
        <h2>Rekomendasi Frame</h2>
        <table>
            <thead>
                <tr>
                    <th>Ranking</th>
                    <th>Merk Frame</th>
                    <th>Harga</th>
                    <th>Skor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasis as $rekomendasi)
                <tr>
                    <td>{{ $rekomendasi->rangking }}</td>
                    <td>{{ $rekomendasi->frame->frame_merek }}</td>
                    <td>Rp. {{ number_format($rekomendasi->frame->frame_harga, 0, ',', '.') }}</td>
                    <td>{{ number_format($rekomendasi->nilai_akhir, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>