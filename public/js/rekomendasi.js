$(document).ready(function() {
    // DataTables initialization
    $('#riwayatTable').DataTable({
        "pageLength": 20,
        "lengthChange": false,
        "order": [[0, 'dsc']],
        "language": {
            "search": "Cari:",
            "paginate": {
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            },
            "info": "Total Data: _TOTAL_",
            "infoEmpty": "Total Data: 0",
            "zeroRecords": "Tidak ada data riwayat penilaian"
        }
    });

    // Delete confirmation
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Anda yakin?',
            text: "Data riwayat rekomendasi akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).unbind('submit').submit();
            }
        });
    });
});