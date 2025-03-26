@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Display incomplete frames warning --}}
    @if(!empty($incompleteFrames))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Penilaian Belum Bisa Dilakukan!</strong> Terdapat {{ count($incompleteFrames) }} frame yang datanya belum lengkap.
        <br>
        <a href="{{ route('frame.index') }}" class="btn btn-sm btn-warning mt-2">Lengkapi Data Frame</a>
    </div>
    @endif

    {{-- Display validation errors --}}
    @if($errors->has('frame_incomplete'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> {{ $errors->first('frame_incomplete') }}
        <a href="{{ route('frame.index') }}" class="btn btn-sm btn-danger mt-2">Lengkapi Data Frame</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <h2>Penilaian Pelanggan</h2>
    
    {{-- Main form for assessment --}}
    <form method="POST" action="{{ route('penilaian.process') }}" id="penilaianForm">
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
            <div class="card-header">Bobot Kriteria (Akan dinormalisasi secara otomatis)</div>
            <div class="card-body">
                <div class="row">
                    @foreach($kriterias as $kriteria)
                    <div class="col-md-3 mb-3">
                        <div class="form-group">
                            <label>{{ $kriteria->kriteria_nama }}</label>
                            <div class="input-group">
                                <input type="number" name="bobot_kriteria[{{ $kriteria->kriteria_id }}]" 
                                    class="form-control bobot-kriteria" min="1" max="100" required
                                    value="100">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="alert alert-info mt-2">
                    <p>Total Bobot: <span id="total-bobot">100</span></p>
                    <p>Bobot Ternormalisasi (bobot/total):</p>
                    <div id="normalized-weights"></div>
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
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        
        <button 
            type="submit" 
            id="submit-btn" 
            class="btn btn-primary mt-3"
            @if(!empty($incompleteFrames)) disabled @endif
        >
            Proses Penilaian
        </button>
    </form>

    {{-- Section to display processed results (initially hidden) --}}
    <div id="hasilPenilaianSection" style="display: none;" class="mt-4">
        <div class="card">
            <div class="card-body" id="hasilPenilaianContent">
                {{-- Dynamic content will be loaded here --}}
            </div>
            <div class="card-footer">
                <button id="editPenilaianBtn" class="btn btn-warning">Edit Penilaian</button>
                <button id="simpanPenilaianBtn" class="btn btn-success">Simpan Rekomendasi</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bobotInputs = document.querySelectorAll('.bobot-kriteria');
    const totalBobotDisplay = document.getElementById('total-bobot');
    const normalizedWeightsDisplay = document.getElementById('normalized-weights');
    const submitBtn = document.getElementById('submit-btn');
    const hasilPenilaianSection = document.getElementById('hasilPenilaianSection');
    const hasilPenilaianContent = document.getElementById('hasilPenilaianContent');
    const editPenilaianBtn = document.getElementById('editPenilaianBtn');
    const simpanPenilaianBtn = document.getElementById('simpanPenilaianBtn');
    const penilaianForm = document.getElementById('penilaianForm');

    // Existing total calculation function remains the same
    function calculateTotal() {
        let total = 0;
        const weights = [];
        
        bobotInputs.forEach(input => {
            const value = parseFloat(input.value || 0);
            total += value;
            const criteriaId = input.name.match(/\[(\d+)\]/)[1];
            weights.push({
                id: criteriaId,
                name: input.closest('.form-group').querySelector('label').textContent,
                value: value
            });
        });
        
        totalBobotDisplay.textContent = total;
        
        if (total > 0) {
            let normalizedHTML = '';
            weights.forEach(weight => {
                const normalized = (weight.value / total).toFixed(4);
                normalizedHTML += `<div>${weight.name}: ${normalized}</div>`;
            });
            normalizedWeightsDisplay.innerHTML = normalizedHTML;
            submitBtn.disabled = @if(!empty($incompleteFrames)) true @else false @endif;
        } else {
            normalizedWeightsDisplay.innerHTML = '<div class="text-danger">Total bobot harus lebih dari 0</div>';
            submitBtn.disabled = true;
        }
    }
    
    bobotInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    // Initial calculation
    calculateTotal();

    // Handle form submission via AJAX
    penilaianForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Collect form data
        const formData = new FormData(penilaianForm);

        // Send AJAX request
        fetch('{{ route('penilaian.process') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Display results
            hasilPenilaianContent.innerHTML = data.html;
            hasilPenilaianSection.style.display = 'block';
            penilaianForm.style.display = 'none';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat memproses penilaian');
        });
    });

    // Edit Penilaian button
    editPenilaianBtn.addEventListener('click', function() {
        hasilPenilaianSection.style.display = 'none';
        penilaianForm.style.display = 'block';
    });

    // Simpan Penilaian button
    simpanPenilaianBtn.addEventListener('click', function() {
        // Send request to save recommendation
        fetch('{{ route('penilaian.store') }}', {
            method: 'POST',
            body: new FormData(penilaianForm),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Redirect to recommendation details
            window.location.href = data.redirect_url;
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menyimpan rekomendasi');
        });
    });
});
</script>
@endsection