$(document).ready(function() {
    // DataTables initialization
    $('#logTable').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "order": [[0, 'desc']], // Mengurutkan berdasarkan waktu (kolom pertama) secara descending
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