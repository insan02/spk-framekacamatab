$(document).ready(function() {
    // DataTables initialization with pagination disabled (using Laravel's pagination instead)
    $('#logTable').DataTable({
        "paging": false,    // Disable DataTables pagination
        "lengthChange": false,
        "info": false,      // Disable the information display
        "order": [[0, 'desc']], // Mengurutkan berdasarkan waktu (kolom pertama) secara descending
        "language": {
            "search": "Cari:",
            "infoEmpty": "",
            "zeroRecords": "Tidak ditemukan data yang cocok"
        }
    });

    // Filter handling
    $('#module, #action, #user_id, #date_from, #date_to').on('change', function() {
        if ($(this).val() !== '') {
            $(this).addClass('bg-light');
        } else {
            $(this).removeClass('bg-light');
        }
    });
    
    // Initialize state for filters that have values
    $('#module, #action, #user_id, #date_from, #date_to').each(function() {
        if ($(this).val() !== '') {
            $(this).addClass('bg-light');
        }
    });
    
    // Reset button functionality
    $('.btn-secondary').on('click', function() {
        $('#module, #action, #user_id').val('');
        $('#date_from, #date_to').val('');
        $('#module, #action, #user_id, #date_from, #date_to').removeClass('bg-light');
    });

    
    
});

document.addEventListener('DOMContentLoaded', function() {
    // Success and error messages handling
    const successMessage = document.querySelector('[data-success-message]');
    const errorMessage = document.querySelector('[data-error-message]');
    
    if (successMessage) {
        Swal.fire({
            title: 'Berhasil!',
            text: successMessage.getAttribute('data-success-message'),
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }
    
    if (errorMessage) {
        Swal.fire({
            title: 'Error!',
            text: errorMessage.getAttribute('data-error-message'),
            icon: 'error',
            confirmButtonText: 'OK'
        });
    }
    
    // Reset all logs button confirmation
    const resetAllLogsBtn = document.getElementById('resetAllLogsBtn');
    if (resetAllLogsBtn) {
        resetAllLogsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteAllModal'));
            deleteModal.show();
        });
    }
    
    // Individual log deletion confirmation
    const deleteForms = document.querySelectorAll('form[action*="logs/"]');
    deleteForms.forEach(form => {
        if (form.method.toLowerCase() === 'post' && form.innerHTML.includes('DELETE')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Apakah Anda yakin ingin menghapus data log ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        }
    });
    
    // Date range validation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    if (dateFrom && dateTo) {
        dateFrom.addEventListener('change', validateDateRange);
        dateTo.addEventListener('change', validateDateRange);
    }
    
    function validateDateRange() {
        if (dateFrom.value && dateTo.value) {
            if (new Date(dateFrom.value) > new Date(dateTo.value)) {
                dateTo.setCustomValidity('Tanggal akhir harus setelah tanggal awal');
                Swal.fire({
                    title: 'Kesalahan!',
                    text: 'Tanggal akhir harus setelah tanggal awal',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else {
                dateTo.setCustomValidity('');
            }
        } else {
            dateTo.setCustomValidity('');
        }
    }
});


