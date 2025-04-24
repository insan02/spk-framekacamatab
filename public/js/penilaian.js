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
    const searchCustomerInput = document.getElementById('searchCustomer');
    const searchCustomerBtn = document.getElementById('searchCustomerBtn');
    const noResults = document.getElementById('noResults');
    const selectedCustomerCard = document.getElementById('selectedCustomerCard');
    const changeCustomerBtn = document.getElementById('changeCustomerBtn');
    const penilaianCard = document.getElementById('penilaianCard');
    const noPelangganAlert = document.getElementById('noPelangganAlert');
    const penilaianInputs = document.querySelectorAll('.penilaian-input');
    const customerTableBody = document.getElementById('customerTableBody');
    const customerSearchSection = document.querySelector('.card.shadow-sm.mb-4');
    const hasIncompleteFrames = document.getElementById('submit-btn') && document.getElementById('submit-btn').hasAttribute('data-incomplete');

    // Initialize DataTable
    $('#customerTable').DataTable({
        "pageLength": 20,
        "lengthChange": false,
        "searching": false,
        "order": [[0, 'dsc']],
        "language": {
            "search": "Cari:",
            "paginate": {
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            },
            "info": "Total Data: _TOTAL_",
            "infoEmpty": "Total Data: 0",
            "zeroRecords": "Tidak ditemukan data yang cocok"
        }
    });

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
                
                // MAKE SURE CUSTOMER SEARCH SECTION STAYS HIDDEN - FIX FOR ISSUE #2
                if (customerSearchSection) {
                    customerSearchSection.style.display = 'none';
                }
                
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
        
        // KEEP CUSTOMER SEARCH SECTION HIDDEN
        if (customerSearchSection) {
            customerSearchSection.style.display = 'none';
        }
        
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

    // Handle search customer
    if (searchCustomerBtn) {
        searchCustomerBtn.addEventListener('click', function() {
            const searchQuery = searchCustomerInput.value.trim();
            if (searchQuery !== '') {
                searchCustomer(searchQuery);
            }
        });
    }
    
    // Allow search on Enter key
    if (searchCustomerInput) {
        searchCustomerInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const searchQuery = searchCustomerInput.value.trim();
                if (searchQuery !== '') {
                    searchCustomer(searchQuery);
                }
            }
        });
    }
    
    // Change selected customer
    if (changeCustomerBtn) {
        changeCustomerBtn.addEventListener('click', function() {
            resetCustomerSelection();
        });
    }
    
    // Add event listeners to select buttons for initial table
    document.querySelectorAll('.select-customer').forEach(button => {
        // Disable buttons if there are incomplete frames
        if (hasIncompleteFrames) {
            button.classList.remove('btn-primary');
            button.classList.add('btn-secondary');
            button.disabled = true;
            button.title = 'Lengkapi data frame terlebih dahulu';
        } else {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-id');
                const customerName = this.getAttribute('data-name');
                const customerPhone = this.getAttribute('data-phone');
                const customerAddress = this.getAttribute('data-address');
                
                selectCustomer(customerId, customerName, customerPhone, customerAddress);
            });
        }
    });
    
    // Search for customer function
    function searchCustomer(query) {
        // Show loading indicator in the table
        if (customerTableBody) {
            customerTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Mencari...</td></tr>';
        }
        
        // AJAX request to search customers
        fetch('/customers/search?q=' + encodeURIComponent(query), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.error || 'Network response was not ok');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.customers && data.customers.length > 0) {
                populateCustomerTable(data.customers);
                if (noResults) {
                    noResults.style.display = 'none';
                }
            } else {
                if (customerTableBody) {
                    customerTableBody.innerHTML = '<tr><td colspan="5" class="text-center">Tidak ada data pelanggan</td></tr>';
                }
                if (noResults) {
                    noResults.style.display = 'block';
                }
            }
        })
        .catch(error => {
            console.error('Error searching customers:', error);
            if (customerTableBody) {
                customerTableBody.innerHTML = `<tr><td colspan="5" class="text-danger">
                    Terjadi kesalahan saat mencari pelanggan: ${error.message}
                </td></tr>`;
            }
        });
    }
    
    // Populate customer table with search results
    // Populate customer table with search results
function populateCustomerTable(customers) {
    if (!customerTableBody) return;

    customerTableBody.innerHTML = '';
    
    // Check if there are incomplete frames
    // Periksa dari beberapa sumber yang mungkin
    const hasIncompleteFrames = 
        // Periksa dari data atribut container
        (document.querySelector('.container[data-incomplete-frames="true"]') !== null) ||
        // Periksa dari tombol submit (cara sebelumnya)
        (document.getElementById('submit-btn') && document.getElementById('submit-btn').hasAttribute('data-incomplete')) ||
        // Periksa dari keberadaan pesan alert
        (document.querySelector('.alert-warning strong') && 
         document.querySelector('.alert-warning strong').textContent.includes('Penilaian Belum Bisa Dilakukan'));
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    customers.forEach((customer, index) => {
        const row = document.createElement('tr');
        
        // Determine button class and disabled state based on incomplete frames
        const buttonClass = hasIncompleteFrames ? 'btn-secondary' : 'btn-primary';
        const buttonDisabled = hasIncompleteFrames ? 'disabled' : '';
        const buttonTitle = hasIncompleteFrames ? 'title="Lengkapi data frame terlebih dahulu"' : '';
        
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${customer.name}</td>
            <td>${customer.phone}</td>
            <td>${customer.address}</td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm ${buttonClass} select-customer" 
                        data-id="${customer.customer_id}"
                        data-name="${customer.name}"
                        data-phone="${customer.phone}"
                        data-address="${customer.address}"
                        ${buttonDisabled}
                        ${buttonTitle}>
                        <i class="fas fa-clipboard-check"></i> Penilaian
                    </button>
                    <a href="/customers/${customer.customer_id}/edit" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="/customers/${customer.customer_id}" method="POST" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        `;
        customerTableBody.appendChild(row);
        
        // Add event listener to select button only if frames are complete
        if (!hasIncompleteFrames) {
            row.querySelector('.select-customer').addEventListener('click', function() {
                selectCustomer(
                    customer.customer_id,
                    customer.name,
                    customer.phone,
                    customer.address
                );
            });
        }
    });
}
    
    // Tambahkan fungsi untuk mengaktifkan/menonaktifkan form penilaian
    function togglePenilaianForm(enable) {
        // Enable/disable semua input form
        penilaianInputs.forEach(input => {
            input.disabled = !enable;
        });
        
        // Enable/disable tombol submit
        if (submitBtn) {
            // Tetap perhatikan kondisi lain (validasi bobot dan frame lengkap)
            const hasIncompleteFrames = submitBtn.hasAttribute('data-incomplete');
            const inputsAreValid = !bobotWarning.style.display || bobotWarning.style.display === 'none';
            
            submitBtn.disabled = !enable || hasIncompleteFrames || !inputsAreValid;
        }
        
        // Tampilkan/sembunyikan pesan alert
        if (noPelangganAlert) {
            noPelangganAlert.style.display = enable ? 'none' : 'block';
        }
    }
    
    // Inisialisasi form dalam keadaan nonaktif
    togglePenilaianForm(false);

    // Select a customer and show penilaian form
    function selectCustomer(id, name, phone, address) {
        // Set the selected customer info
        const selectedCustomerId = document.getElementById('selectedCustomerId');
        const penilaianCustomerId = document.getElementById('penilaianCustomerId');
        const selectedCustomerName = document.getElementById('selectedCustomerName');
        const selectedCustomerPhone = document.getElementById('selectedCustomerPhone');
        const selectedCustomerAddress = document.getElementById('selectedCustomerAddress');
        
        if (selectedCustomerId) selectedCustomerId.value = id;
        if (penilaianCustomerId) penilaianCustomerId.value = id;
        if (selectedCustomerName) selectedCustomerName.textContent = name;
        if (selectedCustomerPhone) selectedCustomerPhone.textContent = phone;
        if (selectedCustomerAddress) selectedCustomerAddress.textContent = address;
        
        // Hide customer search card
        if (customerSearchSection) {
            customerSearchSection.style.display = 'none';
        }
        
        // Show selected customer card
        if (selectedCustomerCard) {
            selectedCustomerCard.style.display = 'block';
        }
        
        // Aktifkan form penilaian
        togglePenilaianForm(true);
        
        // Scroll to the selected customer card
        if (selectedCustomerCard) {
            selectedCustomerCard.scrollIntoView({behavior: 'smooth'});
        }
    }
    
    // Modifikasi resetCustomerSelection untuk menonaktifkan form kembali
    function resetCustomerSelection() {
        // Show customer search section again
        if (customerSearchSection) {
            customerSearchSection.style.display = 'block';
        }
        
        // Hide customer card
        if (selectedCustomerCard) {
            selectedCustomerCard.style.display = 'none';
        }
        
        // Nonaktifkan form penilaian
        togglePenilaianForm(false);
        
        // Clear selected customer info
        const selectedCustomerId = document.getElementById('selectedCustomerId');
        const penilaianCustomerId = document.getElementById('penilaianCustomerId');
        
        if (selectedCustomerId) selectedCustomerId.value = '';
        if (penilaianCustomerId) penilaianCustomerId.value = '';
        
        // Reset form jika ada nilai yang sudah diisi
        if (penilaianForm) {
            penilaianForm.reset();
        }
        
        // Focus on search
        if (searchCustomerInput) {
            searchCustomerInput.focus();
        }
    }
});