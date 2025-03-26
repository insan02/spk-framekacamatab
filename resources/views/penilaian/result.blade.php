<div class="row">
    <div class="col-md-6">
        <h5>Detail Pelanggan</h5>
        <table class="table">
            <tr>
                <th>Nama</th>
                <td>{{ $nama_pelanggan }}</td>
            </tr>
            <tr>
                <th>No HP</th>
                <td>{{ $nohp_pelanggan }}</td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td>{{ $alamat_pelanggan }}</td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5>Kriteria yang Dipilih</h5>
        <table class="table">
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

<div class="row mt-4">
    <div class="col-md-12">
        <h5>Hasil Rekomendasi</h5>
        <table class="table">
            <thead>
                <tr>
                    <th>Ranking</th>
                    <th>Frame</th>
                    <th>Skor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekomendasi as $index => $frame)
                <tr class="{{ $index === 0 ? 'table-primary' : ($index === 1 ? 'table-info' : '') }}">
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $frame['frame']->frame_merek }}</td>
                    <td>{{ number_format($frame['score'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>