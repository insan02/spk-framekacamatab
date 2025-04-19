$(document).ready(function() {
    $('#imageSearchForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#searchResults').html('');
                    if (response.count > 0) {
                        $('#searchResultsContainer').show();
                        $.each(response.frames, function(index, frame) {
                            // Pastikan path gambar benar
                            var fotoUrl = frame.foto.startsWith('http') ? frame.foto : "{{ asset('storage') }}/" + frame.foto;

                            $('#searchResults').append(`
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <img src="${fotoUrl}" class="img-fluid mb-3" style="max-height: 200px;">
                                            <p><strong>${frame.merek}</strong></p>
                                            <p>Harga: Rp ${frame.harga}</p>
                                            <a href="${frame.url}" class="btn btn-primary">Detail</a>
                                        </div>
                                    </div>
                                </div>
                            `);
                        });
                    } else {
                        $('#searchResultsContainer').show();
                        $('#searchResults').html('<div class="col-12 text-center">Tidak ditemukan hasil.</div>');
                    }
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan. Silakan coba lagi.');
                console.error(xhr.responseText);
            }
        });
    });
});