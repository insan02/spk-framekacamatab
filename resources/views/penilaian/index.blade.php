@extends('layouts.app')

@section('content')
{{-- Include SweetAlert CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Penilaian Pelanggan</h2>
        <div>
            <button 
                id="editPenilaianBtn" 
                class="btn btn-warning me-2" 
                style="display: none;"
            >
                Edit Penilaian
            </button>
            <button 
                id="simpanPenilaianBtn" 
                class="btn btn-success" 
                style="display: none;"
            >
                Simpan Rekomendasi
            </button>
        </div>
    </div>
    
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
            <div class="card-header">Bobot Kriteria</div>
            <div class="card-body">
                <div class="row">
                    @foreach($kriterias as $kriteria)
                    <div class="col-md-12 mb-3">
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
                <div id="bobot-warning" class="alert alert-warning" style="display: none;">
                    Peringatan: Setiap kriteria harus memiliki bobot antara 1 dan 100
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
                        <div class="col-md-12 mb-2">
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
        
        <div class="d-flex justify-content-between mt-3">
            <div>
                <button 
                    type="submit" 
                    id="submit-btn" 
                    class="btn btn-primary me-2"
                    @if(!empty($incompleteFrames)) disabled @endif
                >
                    Proses Penilaian
                </button>
            
                <button
                    id="batalEditBtn" 
                    class="btn btn-secondary" 
                    style="display: none;"
                >
                    Batal Edit
                </button>
            </div>
        </div>
        
    </form>

    {{-- Section to display processed results (initially hidden) --}}
    <div id="hasilPenilaianSection" style="display: none;" class="mt-4">
        <div class="card">
            <div class="card-body" id="hasilPenilaianContent">
                {{-- Dynamic content will be loaded here --}}
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bobotInputs = document.querySelectorAll('.bobot-kriteria');
        const submitBtn = document.getElementById('submit-btn');
        const hasilPenilaianSection = document.getElementById('hasilPenilaianSection');
        const hasilPenilaianContent = document.getElementById('hasilPenilaianContent');
        const editPenilaianBtn = document.getElementById('editPenilaianBtn');
        const batalEditBtn = document.getElementById('batalEditBtn');
        const simpanPenilaianBtn = document.getElementById('simpanPenilaianBtn');
        const penilaianForm = document.getElementById('penilaianForm');
        const bobotWarning = document.getElementById('bobot-warning');
    
        function validateBobotInputs() {
            let isValid = true;
            
            bobotInputs.forEach(input => {
                const value = parseInt(input.value);
                if (value < 1 || value > 100) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });
    
            // Show/hide warning
            bobotWarning.style.display = !isValid ? 'block' : 'none';
            
            // Disable submit button if inputs are invalid
            submitBtn.disabled = !isValid || @if(!empty($incompleteFrames)) true @else false @endif;
        }
        
        bobotInputs.forEach(input => {
            input.addEventListener('input', validateBobotInputs);
        });
        
        // Initial validation
        validateBobotInputs();
    
        // Handle form submission via AJAX
        penilaianForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if in edit mode
            const isEditMode = batalEditBtn.style.display !== 'none';
            
            // If in edit mode, show SweetAlert confirmation
            if (isEditMode) {
                Swal.fire({
                    title: 'Konfirmasi Edit',
                    text: 'Apakah Anda yakin ingin mengedit data penilaian?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Edit',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        processPenilaian();
                    }
                });
                return;
            }
            
            // If not in edit mode, directly process
            processPenilaian();
        });
    
        function processPenilaian() {
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
            .then(response => {
                // Tambahkan pengecekan status response
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.error || 'Terjadi kesalahan saat memproses penilaian');
                    });
                }
                return response.json();
            })
            .then(data => {
                // Pastikan data.html tersedia
                if (data.html) {
                    // Display results
                    hasilPenilaianContent.innerHTML = data.html;
                    hasilPenilaianSection.style.display = 'block';
                    penilaianForm.style.display = 'none';
                    
                    // Show edit and save buttons
                    editPenilaianBtn.style.display = 'inline-block';
                    simpanPenilaianBtn.style.display = 'inline-block';
                    
                    // Hide batal edit button if in edit mode
                    batalEditBtn.style.display = 'none';
                    submitBtn.style.display = 'inline-block';
                } else {
                    throw new Error('Data hasil tidak lengkap');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan',
                    text: error.message || 'Terjadi kesalahan saat memproses penilaian'
                });
            });
        }
    
        // Edit Penilaian button
        editPenilaianBtn.addEventListener('click', function() {
            penilaianForm.style.display = 'block';
            hasilPenilaianSection.style.display = 'none';
            
            // Show Batal Edit and Proses Penilaian buttons, hide other buttons
            batalEditBtn.style.display = 'inline-block';
            submitBtn.style.display = 'inline-block';
            editPenilaianBtn.style.display = 'none';
            simpanPenilaianBtn.style.display = 'none';
        });
    
        // Batal Edit button
        batalEditBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Batalkan Edit',
                text: 'Apakah Anda yakin ingin membatalkan perubahan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Tidak'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Hide form and show results again
                    penilaianForm.style.display = 'none';
                    hasilPenilaianSection.style.display = 'block';
                    
                    // Restore original buttons
                    batalEditBtn.style.display = 'none';
                    submitBtn.style.display = 'none';
                    editPenilaianBtn.style.display = 'inline-block';
                    simpanPenilaianBtn.style.display = 'inline-block';
                }
            });
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
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Rekomendasi berhasil disimpan',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = data.redirect_url;
                });
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan',
                    text: 'Terjadi kesalahan saat menyimpan rekomendasi'
                });
            });
        });
    });
    </script>
@endsection