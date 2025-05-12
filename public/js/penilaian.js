document.addEventListener('DOMContentLoaded', function() {
    // Element references
    const bobotInputs = document.querySelectorAll('.bobot-kriteria');
    const submitBtn = document.getElementById('submit-btn');
    const hasilPenilaianSection = document.getElementById('hasilPenilaianSection');
    const hasilPenilaianContent = document.getElementById('hasilPenilaianContent');
    const editPenilaianBtn = document.getElementById('editPenilaianBtn');
    const batalEditBtn = document.getElementById('batalEditBtn');
    const simpanPenilaianBtn = document.getElementById('saveRecommendationBtn');
    const penilaianForm = document.getElementById('penilaianForm');
    const bobotWarning = document.getElementById('bobot-warning');
    const searchCustomerInput = document.getElementById('searchCustomer');
    const searchCustomerBtn = document.getElementById('searchCustomerBtn');
    const noResults = document.getElementById('noResults');
    const selectedCustomerCard = document.getElementById('selectedCustomerCard');
    const penilaianCard = document.getElementById('penilaianCard');
    const noPelangganAlert = document.getElementById('noPelangganAlert');
    const penilaianInputs = document.querySelectorAll('.penilaian-input');
    const customerTableBody = document.getElementById('customerTableBody');
    const customerSelectionCard = document.getElementById('customerSelectionCard');
    const hasilPenilaianCard = document.getElementById('hasilPenilaianCard');
    const progressBar = document.getElementById('wizard-progress-bar');
    const wizardSteps = document.querySelectorAll('.wizard-step');
    const hasIncompleteFrames = document.getElementById('submit-btn') && document.getElementById('submit-btn').hasAttribute('data-incomplete');
    
    // Debug elements to console
    console.log('Debug simpanPenilaianBtn element:', simpanPenilaianBtn);
    
    const prevToCustomerFromFormBtn = document.getElementById('prevToCustomerFromFormBtn');
    const backToFormFromResultBtn = document.getElementById('backToFormFromResultBtn');
    const editFromResultBtn = document.getElementById('editFromResultBtn');
    const saveRecommendationBtn = document.getElementById('saveRecommendationBtn');

    // Initialize DataTable
    if ($.fn.DataTable && $('#customerTable').length) {
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
    }

    // Show success message if exists
    const successMessage = document.querySelector('[data-success-message]');
    if (successMessage) {
        const message = successMessage.getAttribute('data-success-message');
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: message,
            timer: 2000
        });
    }

    // Function to update wizard step UI
    function updateWizardStep(stepNumber) {
        // Update progress bar
        const progress = (stepNumber / wizardSteps.length) * 100;
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
        
        // Update step icons
        wizardSteps.forEach((step, index) => {
            if (index < stepNumber) {
                step.classList.add('completed');
                step.classList.add('active');
            } else if (index === stepNumber - 1) {
                step.classList.add('active');
                step.classList.remove('completed');
            } else {
                step.classList.remove('active');
                step.classList.remove('completed');
            }
        });
    }

    // Function to show specific wizard step
    function showWizardStep(stepNumber) {
        // Hide all cards
        customerSelectionCard.style.display = 'none';
        selectedCustomerCard.style.display = 'none';
        penilaianCard.style.display = 'none';
        hasilPenilaianCard.style.display = 'none';
        
        // Show specific card based on step
        switch(stepNumber) {
            case 1:
                customerSelectionCard.style.display = 'block';
                break;
            case 2:
                selectedCustomerCard.style.display = 'block';
                penilaianCard.style.display = 'block';
                break;
            case 3:
                selectedCustomerCard.style.display = 'block';
                hasilPenilaianCard.style.display = 'block';
                break;
            case 4:
                selectedCustomerCard.style.display = 'block';
                hasilPenilaianCard.style.display = 'block';
                // Additional settings for recommendation step could be added here
                break;
        }
        
        // Update wizard UI
        updateWizardStep(stepNumber);
    }

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
            loadingOverlay.style.display = 'none';
            loadingOverlay.style.position = 'fixed';
            loadingOverlay.style.top = '0';
            loadingOverlay.style.left = '0';
            loadingOverlay.style.width = '100%';
            loadingOverlay.style.height = '100%';
            loadingOverlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
            loadingOverlay.style.zIndex = '9999';
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.justifyContent = 'center';
            loadingOverlay.style.alignItems = 'center';
            loadingOverlay.style.flexDirection = 'column';
            loadingOverlay.style.color = 'white';
            
            loadingOverlay.innerHTML = `
                <div class="spinner-border text-light" role="status">
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
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
                
                // Show hasil penilaian section
                showWizardStep(3);
                
                // Hide form and show result
                penilaianForm.style.display = 'none';
                hasilPenilaianSection.style.display = 'block';
                
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
    $('#hasilPerangkinganTable').DataTable({
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
    
    // Untuk tabel 1-4, tetap gunakan pengurutan tapi atur kolom pertama sebagai urutan default
    $('#nilaiProfileFrameTable, #perhitunganGapTable, #konversiNilaiGapTable, #nilaiAkhirSMARTTable').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "order": [], // Kosongkan order default agar data tetap dalam urutan original dari database
        "columnDefs": [
            { "orderable": false, "targets": "_all" } // Nonaktifkan kemampuan pengurutan untuk semua kolom
        ],
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
    if (penilaianForm) {
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
    }

    // Edit Penilaian button event listener
    if (editPenilaianBtn) {
        editPenilaianBtn.addEventListener('click', function() {
            showWizardStep(2);
            hasilPenilaianSection.style.display = 'none';
            penilaianForm.style.display = 'block';
            
            // Show Batal Edit and Proses Penilaian buttons, hide other buttons
            batalEditBtn.style.display = 'inline-block';
            submitBtn.style.display = 'inline-block';
            editPenilaianBtn.style.display = 'none';
            simpanPenilaianBtn.style.display = 'none';
        });
    }

    // Batal Edit button event listener
    if (batalEditBtn) {
        batalEditBtn.addEventListener('click', function() {
            // Directly revert to results view without SweetAlert
            showWizardStep(3);
            penilaianForm.style.display = 'none';
            hasilPenilaianSection.style.display = 'block';
            
            // Restore original buttons
            batalEditBtn.style.display = 'none';
            submitBtn.style.display = 'none';
            editPenilaianBtn.style.display = 'inline-block';
            simpanPenilaianBtn.style.display = 'inline-block';
        });
    }

    // Modified Save Recommendation button handler
    // Modified Save Recommendation button handler
if (saveRecommendationBtn) {
    saveRecommendationBtn.addEventListener('click', function() {
        console.log('Save recommendation button clicked!');
        
        // Show loading spinner
        showLoading();
        
        // Update wizard to step 4 (recommendation)
        updateWizardStep(4);
        
        // Get the form data
        const formData = new FormData(penilaianForm);
        
        // Make sure customer ID is included in the form data
        const customerId = document.getElementById('selectedCustomerId').value || 
                           document.getElementById('penilaianCustomerId').value;
                           
        if (customerId) {
            formData.set('customer_id', customerId);
        }
        
        // Add missing results data if needed
        // Adding a flag to indicate this is a save action, not just processing
        formData.append('action', 'save_recommendation');
        
        // Log form data for debugging
        console.log('Form data entries:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (!csrfToken) {
            console.error('CSRF token not found!');
            hideLoading();
            
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan',
                text: 'CSRF token tidak ditemukan'
            });
            return;
        }
        
        // Use the correct URL for storing recommendations
        const storeUrl = '/penilaian/store';
        
        // Send request to save recommendation with improved error handling
        fetch(storeUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.getAttribute('content')
            },
            credentials: 'same-origin'
        })
        .then(response => {
            // Better response handling
            if (!response.ok) {
                // Try to get JSON error first
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON
                        const json = JSON.parse(text);
                        throw new Error(json.error || `HTTP error: ${response.status}`);
                    } catch (e) {
                        // If not JSON or other parsing error, return the raw text
                        console.error('Response text:', text);
                        throw new Error(`HTTP error: ${response.status}`);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            // Hide loading spinner
            hideLoading();
            
            // Check if there's an error
            if (data.error) {
                throw new Error(data.error);
            }

            // Success message and redirect
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Rekomendasi berhasil disimpan',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                // FIXED: More robust redirect handling
                try {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else if (data.recommendation_history_id) {
                        // Using string concatenation to ensure we have an ID
                        window.location.href = '/rekomendasi/' + data.recommendation_history_id;
                    } else if (data.id) {
                        // Fallback for different ID naming
                        window.location.href = '/rekomendasi/' + data.id;
                    } else {
                        // If no ID is available, go to the index
                        window.location.href = '/rekomendasi';
                    }
                } catch (e) {
                    console.error('Redirect error:', e);
                    // Fallback to the index page if any errors occur
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
}

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
    
    // Back to Customer Selection from Form
    if (prevToCustomerFromFormBtn) {
        prevToCustomerFromFormBtn.addEventListener('click', function() {
            showWizardStep(1);
            resetCustomerSelection();
        });
    }
    
    // Edit from Result Button
    if (editFromResultBtn) {
        editFromResultBtn.addEventListener('click', function() {
            showWizardStep(2);
            penilaianForm.style.display = 'block';
            hasilPenilaianSection.style.display = 'none';
            
            // Show Batal Edit and Proses Penilaian buttons, hide other buttons
            if (batalEditBtn) batalEditBtn.style.display = 'inline-block';
            if (submitBtn) submitBtn.style.display = 'inline-block';
            if (editPenilaianBtn) editPenilaianBtn.style.display = 'none';
            if (simpanPenilaianBtn) simpanPenilaianBtn.style.display = 'none';
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
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
    function populateCustomerTable(customers) {
        if (!customerTableBody) return;

        customerTableBody.innerHTML = '';
        
        // Check if there are incomplete frames
        const hasIncompleteFrames = 
            (document.querySelector('.container[data-incomplete-frames="true"]') !== null) ||
            (document.getElementById('submit-btn') && document.getElementById('submit-btn').hasAttribute('data-incomplete')) ||
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
                            <i class="fas fa-clipboard-check"></i> Pilih
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
    
    // Function to enable/disable assessment form
    function togglePenilaianForm(enable) {
        // Enable/disable all form inputs
        penilaianInputs.forEach(input => {
            input.disabled = !enable;
        });
        
        // Enable/disable submit button
        if (submitBtn) {
            // Consider other conditions (bobot validation and complete frames)
            const hasIncompleteFrames = submitBtn.hasAttribute('data-incomplete');
            const inputsAreValid = !bobotWarning.style.display || bobotWarning.style.display === 'none';
            
            submitBtn.disabled = !enable || hasIncompleteFrames || !inputsAreValid;
        }
        
        // Show/hide alert message
        if (noPelangganAlert) {
            noPelangganAlert.style.display = enable ? 'none' : 'block';
        }
    }
    
    // Initialize form in disabled state
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
        
        // Show wizard step 2 (selected customer and assessment form)
        showWizardStep(2);
        
        // Enable assessment form
        togglePenilaianForm(true);
        
        // Scroll to the selected customer card
        if (selectedCustomerCard) {
            selectedCustomerCard.scrollIntoView({behavior: 'smooth'});
        }
    }
    
    // Reset customer selection
    function resetCustomerSelection() {
        // Show wizard step 1 (customer selection)
        showWizardStep(1);
        
        // Disable assessment form
        togglePenilaianForm(false);
        
        // Clear selected customer info
        const selectedCustomerId = document.getElementById('selectedCustomerId');
        const penilaianCustomerId = document.getElementById('penilaianCustomerId');
        
        if (selectedCustomerId) selectedCustomerId.value = '';
        if (penilaianCustomerId) penilaianCustomerId.value = '';
        
        // Reset form if there are values already filled
        if (penilaianForm) {
            penilaianForm.reset();
        }
        
        // Focus on search
        if (searchCustomerInput) {
            searchCustomerInput.focus();
        }
    }
    
    // Initialize wizard at step 1
    updateWizardStep(1);
    
    // Add diagnostic console output when page loads
    console.log('DOM fully loaded. Page initialization complete.');
});