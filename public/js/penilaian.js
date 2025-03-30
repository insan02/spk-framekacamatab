document.addEventListener('DOMContentLoaded', function() {
    // Element references
    const bobotInputs = document.querySelectorAll('.bobot-kriteria');
    const submitBtn = document.getElementById('submit-btn');
    const hasilPenilaianSection = document.getElementById('hasilPenilaianSection');
    const hasilPenilaianContent = document.getElementById('hasilPenilaianContent');
    const editPenilaianBtn = document.getElementById('editPenilaianBtn');
    const batalEditBtn = document.getElementById('batalEditBtn');
    const simpanPenilaianBtn = document.getElementById('simpanPenilaianBtn');
    const penilaianForm = document.getElementById('penilaianForm');
    const bobotWarning = document.getElementById('bobot-warning');

    // Function to validate bobot inputs
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
        
        // Disable submit button if inputs are invalid or if there are incomplete frames
        const hasIncompleteFrames = submitBtn.hasAttribute('data-incomplete');
        submitBtn.disabled = !isValid || hasIncompleteFrames;
    }
    
    // Add event listeners to bobot inputs
    bobotInputs.forEach(input => {
        input.addEventListener('input', validateBobotInputs);
    });
    
    // Initial validation
    validateBobotInputs();

    // Show loading spinner
    function showLoading() {
        // Create loading overlay if it doesn't exist
        if (!document.getElementById('loading-overlay')) {
            const loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loading-overlay';
            loadingOverlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memproses penilaian...</p>
            `;
            document.body.appendChild(loadingOverlay);
        }
        
        // Show the loading overlay
        document.getElementById('loading-overlay').style.display = 'flex';
    }

    // Hide loading spinner
    function hideLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

    // Process assessment function
    function processPenilaian() {
        // Show loading spinner
        showLoading();
        
        // Collect form data
        const formData = new FormData(penilaianForm);

        // Send AJAX request
        fetch(penilaianForm.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            // Check response status
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.error || 'Terjadi kesalahan saat memproses penilaian');
                });
            }
            return response.json();
        })
        .then(data => {
            // Hide loading spinner
            hideLoading();
            
            // Ensure data.html is available
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
                
                // Initialize DataTables for results if jQuery and DataTables are loaded
                if (typeof $ !== 'undefined' && $.fn.DataTable) {
                    initializeDataTables();
                }
            } else {
                throw new Error('Data hasil tidak lengkap');
            }
        })
        .catch(error => {
            // Hide loading spinner
            hideLoading();
            
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan',
                text: error.message || 'Terjadi kesalahan saat memproses penilaian'
            });
        });
    }

    // Initialize DataTables function
    function initializeDataTables() {
        $('#hasilPerangkinganTable, #nilaiProfileFrameTable, #perhitunganGapTable, #konversiNilaiGapTable, #nilaiAkhirSMARTTable').DataTable({
            "pageLength": 10,
            "lengthChange": false,
            "language": {
                "search": "Cari:",
                "paginate": {
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                },
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Tidak ada data yang ditampilkan",
                "zeroRecords": "Tidak ditemukan data yang cocok"
            }
        });

        // Modal image lightbox functionality
        $('.img-fluid, .img-thumbnail').on('click', function() {
            const src = $(this).attr('src');
            const alt = $(this).attr('alt');
            
            const lightboxHtml = `
                <div class="modal fade" id="imageLightbox" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${alt}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="${src}" class="img-fluid" alt="${alt}">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(lightboxHtml);
            $('#imageLightbox').modal('show');
            
            $('#imageLightbox').on('hidden.bs.modal', function () {
                $(this).remove();
            });
        });
    }

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

    // Edit Penilaian button event listener
    editPenilaianBtn.addEventListener('click', function() {
        penilaianForm.style.display = 'block';
        hasilPenilaianSection.style.display = 'none';
        
        // Show Batal Edit and Proses Penilaian buttons, hide other buttons
        batalEditBtn.style.display = 'inline-block';
        submitBtn.style.display = 'inline-block';
        editPenilaianBtn.style.display = 'none';
        simpanPenilaianBtn.style.display = 'none';
    });

    // Batal Edit button event listener
    batalEditBtn.addEventListener('click', function() {
        // Directly revert to results view without SweetAlert
        penilaianForm.style.display = 'none';
        hasilPenilaianSection.style.display = 'block';
        
        // Restore original buttons
        batalEditBtn.style.display = 'none';
        submitBtn.style.display = 'none';
        editPenilaianBtn.style.display = 'inline-block';
        simpanPenilaianBtn.style.display = 'inline-block';
    });

    // Simpan Penilaian button event listener
    simpanPenilaianBtn.addEventListener('click', function() {
        // Show loading spinner
        showLoading();
        
        // Send request to save recommendation
        fetch('/penilaian/store', {
            method: 'POST',
            body: new FormData(penilaianForm),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Hide loading spinner
            hideLoading();
            
            // Check if there's an error
            if (data.error) {
                throw new Error(data.error);
            }

            // Redirect to recommendation details
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Rekomendasi berhasil disimpan',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // Use redirect_url if available, otherwise fallback
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else if (data.recommendation_history_id) {
                    window.location.href = '/rekomendasi/' + data.recommendation_history_id;
                } else {
                    // Fallback redirect
                    window.location.href = '/rekomendasi';
                }
            });
        })
        .catch(error => {
            // Hide loading spinner
            hideLoading();
            
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan',
                text: error.message || 'Terjadi kesalahan saat menyimpan rekomendasi'
            });
        });
    });
});