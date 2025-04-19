document.addEventListener('DOMContentLoaded', function() {
    // DataTables initialization (if on index page)
    if ($('#frameTable').length > 0) {
        $('#frameTable').DataTable({
            "pageLength": 20,
            "lengthChange": false,
            "searching": false, 
            "order": [[0, 'asc']],
            "language": {
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

    // Price input formatting
    const hargaInputs = document.querySelectorAll('input[name="frame_harga"]');
    
    hargaInputs.forEach(function(hargaInput) {
        // Format input on typing
        hargaInput.addEventListener('input', function(e) {
            // Remove non-numeric characters
            let value = this.value.replace(/[^\d]/g, '');
            
            // Format with thousands separator
            this.value = value ? 'Rp ' + value.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        });

        // Ensure initial value is formatted correctly
        const initialValue = hargaInput.value.replace(/[^\d]/g, '');
        hargaInput.value = initialValue ? 'Rp ' + initialValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    });

    // Handle form submissions for create and edit operations differently
    const formElements = document.querySelectorAll('form');
    formElements.forEach(function(form) {
        // Check if this is a frame form
        if (form.action.includes('/frame') || form.id === 'frameForm') {
            form.addEventListener('submit', function(e) {
                // Check if this is an edit operation
                const isEdit = window.location.href.includes('/edit');
                
                // If it's an edit operation, show confirmation dialog first
                if (isEdit) {
                    e.preventDefault(); // Stop the default form submission
                    
                    // Use SweetAlert2 if available, otherwise use standard confirm
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Konfirmasi',
                            text: 'Apakah Anda yakin ingin memperbarui data frame ini?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, perbarui!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Clean price inputs before submission
                                const priceInputs = form.querySelectorAll('input[name="frame_harga"]');
                                priceInputs.forEach(function(input) {
                                    input.value = input.value.replace(/[^\d]/g, '');
                                });
                                
                                // Show loading animation
                                showLoading();
                                
                                // Submit the form
                                form.submit();
                            }
                        });
                    } else {
                        // Fallback to standard confirm dialog if SweetAlert is not available
                        if (confirm('Apakah Anda yakin ingin memperbarui data frame ini?')) {
                            // Clean price inputs before submission
                            const priceInputs = form.querySelectorAll('input[name="frame_harga"]');
                            priceInputs.forEach(function(input) {
                                input.value = input.value.replace(/[^\d]/g, '');
                            });
                            
                            // Show loading animation
                            showLoading();
                            
                            // Submit the form
                            form.submit();
                        }
                    }
                } else {
                    // For create (add) operation, just show loading and continue
                    showLoading();
                    
                    // Clean price inputs before submission
                    const priceInputs = form.querySelectorAll('input[name="frame_harga"]');
                    priceInputs.forEach(function(input) {
                        input.value = input.value.replace(/[^\d]/g, '');
                    });
                    
                    // Let the form submit normally
                    return true;
                }
            });
        }
    });

    // Show loading spinner
    function showLoading() {
        // Create loading overlay if it doesn't exist
        if (!document.getElementById('loading-overlay')) {
            const loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loading-overlay';
            loadingOverlay.style.position = 'fixed';
            loadingOverlay.style.top = '0';
            loadingOverlay.style.left = '0';
            loadingOverlay.style.width = '100%';
            loadingOverlay.style.height = '100%';
            loadingOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
            loadingOverlay.style.display = 'flex';
            loadingOverlay.style.justifyContent = 'center';
            loadingOverlay.style.alignItems = 'center';
            loadingOverlay.style.zIndex = '9999';
            loadingOverlay.style.flexDirection = 'column';
            loadingOverlay.style.color = 'white';
            
            // Determine if this is add or edit based on URL
            const isEdit = window.location.href.includes('/edit');
            const actionText = isEdit ? 'Mengubah' : 'Menambahkan';
            
            loadingOverlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">${actionText} data frame...</p>
            `;
            document.body.appendChild(loadingOverlay);
        }
        
        // Show the loading overlay
        document.getElementById('loading-overlay').style.display = 'flex';
    }

    // Hide loading spinner (useful for AJAX operations)
    function hideLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

    // Handle delete buttons (if any) with confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete-frame');
    if (deleteButtons.length > 0) {
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Use SweetAlert if available
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Konfirmasi',
                        text: 'Apakah Anda yakin ingin menghapus frame ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            showLoading();
                            window.location.href = this.getAttribute('href');
                        }
                    });
                } else {
                    // Fallback to standard confirm
                    if (confirm('Apakah Anda yakin ingin menghapus frame ini?')) {
                        showLoading();
                        window.location.href = this.getAttribute('href');
                    }
                }
            });
        });
    }
});