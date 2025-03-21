@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Penilaian Pelanggan</h2>
    <form method="POST" action="{{ route('penilaian.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header">Data Pelanggan</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Nama Pelanggan</label>
                            <input type="text" name="nama_pelanggan" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>No HP</label>
                            <input type="text" name="nohp_pelanggan" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Alamat</label>
                            <input type="text" name="alamat_pelanggan" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Bobot Kriteria (Total Harus 100%)</div>
            <div class="card-body">
                <div class="row">
                    @foreach($kriterias as $kriteria)
                    <div class="col-md-3 mb-3">
                        <div class="form-group">
                            <label>{{ $kriteria->kriteria_nama }}</label>
                            <div class="input-group">
                                <input type="number" name="bobot_kriteria[{{ $kriteria->kriteria_id }}]" 
                                       class="form-control bobot-kriteria" min="0" max="100" required
                                       value="{{ 100 / count($kriterias) }}">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="alert alert-info mt-2">
                    <span>Total Bobot: <span id="total-bobot">100</span>%</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Kriteria Frame</div>
            <div class="card-body">
                @foreach($kriterias as $kriteria)
                <div class="mb-4">
                    <h5>{{ $kriteria->kriteria_nama }}</h5>
                    <div class="row">
                        @foreach($kriteria->subkriterias as $subkriteria)
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" 
                                    name="subkriteria[{{ $kriteria->kriteria_id }}]" 
                                    value="{{ $subkriteria->subkriteria_id }}" 
                                    id="sub{{ $subkriteria->subkriteria_id }}" required>
                                <label class="form-check-label" for="sub{{ $subkriteria->subkriteria_id }}">
                                    {{ $subkriteria->subkriteria_nama }}
                                    (Bobot: {{ $subkriteria->subkriteria_bobot }})
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <button type="submit" id="submit-btn" class="btn btn-primary mt-3">Proses Penilaian</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bobotInputs = document.querySelectorAll('.bobot-kriteria');
    const totalBobotDisplay = document.getElementById('total-bobot');
    const submitBtn = document.getElementById('submit-btn');
    
    function calculateTotal() {
        let total = 0;
        bobotInputs.forEach(input => {
            total += parseFloat(input.value || 0);
        });
        
        totalBobotDisplay.textContent = total;
        
        // Validate total - enable/disable submit button
        if (total === 100) {
            totalBobotDisplay.style.color = 'green';
            submitBtn.disabled = false;
        } else {
            totalBobotDisplay.style.color = 'red';
            submitBtn.disabled = true;
        }
    }
    
    bobotInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    // Initial calculation
    calculateTotal();
});
</script>
@endsection