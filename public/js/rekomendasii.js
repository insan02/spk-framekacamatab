$(document).ready(function() {
    // Display success message handling
    initializeSuccessMessage();
    
    // Initialize all datatables with appropriate configurations
    initializeDataTables();
    
    // Apply additional styling for better table appearance
    improveTableAppearance();
});

/**
 * Initialize success message using SweetAlert2
 */
function initializeSuccessMessage() {
    const successElement = $('[data-success-message]');
    
    if (successElement.length) {
        const message = successElement.data('success-message');
        
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: message,
            timer: 3000,
            showConfirmButton: false
        });
    }
}

/**
 * Initialize all DataTables with appropriate configurations
 */
function initializeDataTables() {
    // Base DataTable configuration
    const baseConfig = getBaseDataTableConfig();
    
    // Initialize ranking table with specific configuration
    initializeRankingTable(baseConfig);
    
    // Initialize calculation tables
    initializeCalculationTables(baseConfig);
}

/**
 * Get base DataTable configuration
 * @returns {Object} Base configuration object
 */
function getBaseDataTableConfig() {
    return {
        "pageLength": 20,
        "lengthChange": false,
        "responsive": true,
        "autoWidth": false,
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
    };
}

/**
 * Initialize the ranking table with special configuration
 * @param {Object} baseConfig - Base configuration for DataTable
 */
function initializeRankingTable(baseConfig) {
    $('#hasilPerangkinganTable').DataTable({
        ...baseConfig,
        "order": [[0, 'asc']], // Sort by ranking column
        "columnDefs": [
            { "orderable": false, "targets": [1] } // Disable sorting for image column
        ]
    });
}

/**
 * Initialize all calculation tables
 * @param {Object} baseConfig - Base configuration for DataTable
 */
function initializeCalculationTables(baseConfig) {
    const calculationTables = [
        '#nilaiProfileFrameTable', 
        '#perhitunganGapTable', 
        '#konversiNilaiGapTable', 
        '#nilaiAkhirSMARTTable'
    ];
    
    calculationTables.forEach(tableId => {
        $(tableId).DataTable({
            ...baseConfig,
            "scrollX": true, // Enable horizontal scrolling for tables with many criteria columns
            "scrollCollapse": true,
            "autoWidth": true, // Enable auto width
            "columnDefs": [
                { "width": "auto", "targets": "_all" } // Apply auto width to all columns
            ]
        });
    });
}

/**
 * Apply additional styling for better table appearance
 */
function improveTableAppearance() {
    // Add custom CSS for table display
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .dataTables_wrapper {
                width: 100% !important;
                margin-bottom: 20px;
            }
            .dataTables_scrollHeadInner, 
            .dataTables_scrollHeadInner table,
            .dataTables_scrollBody,
            .dataTables_scrollBody table {
                width: 100% !important;
            }
            .dataTables_scrollHeadInner table,
            .dataTables_scrollBody table {
                table-layout: fixed !important;
            }
            .dataTables_wrapper .dataTable th, 
            .dataTables_wrapper .dataTable td {
                white-space: normal;
                word-break: break-word;
            }
            /* Fix for horizontal scrolling tables */
            .dataTables_scroll {
                overflow-x: auto;
                width: 100%;
            }
            /* Make sure pagination is visible */
            .dataTables_paginate {
                margin-top: 10px !important;
                display: block !important;
            }
            /* Make sure info is visible */
            .dataTables_info {
                margin-top: 10px !important;
                display: block !important;
            }
        `)
        .appendTo('head');

    // Make sure all DataTables elements are properly sized
    setTimeout(function() {
        $(window).trigger('resize');
        $('.dataTables_wrapper').each(function() {
            $(this).find('.dataTables_scroll').css('width', '100%');
        });
    }, 300);
}