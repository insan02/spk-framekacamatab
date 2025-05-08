$(document).ready(function() {
    // Toggle tampilan form berdasarkan tipe subkriteria
    $('#tipe-subkriteria').change(function() {
        if ($(this).val() === 'rentang nilai') {
            $('#subkriteria-teks').hide();
            $('#subkriteria-angka').hide();
            $('#subkriteria-numerik').show();
            handleOperatorChange(); // Panggil fungsi untuk mengatur tampilan awal
        } else if ($(this).val() === 'angka') {
            $('#subkriteria-teks').hide();
            $('#subkriteria-angka').show();
            $('#subkriteria-numerik').hide();
            updateAngkaPreview();
        } else {
            $('#subkriteria-teks').show();
            $('#subkriteria-angka').hide();
            $('#subkriteria-numerik').hide();
        }
    });
    
    // Logika tampilan berdasarkan operator
    $('#operator').change(function() {
        handleOperatorChange();
    });
    
    // Update preview saat nilai berubah
    $('.nilai-numerik').on('input', function() {
        updatePreview();
    });
    
    // Update preview angka
    $('#subkriteria_nilai_angka, #subkriteria_satuan').on('input', function() {
        updateAngkaPreview();
    });
    
    // Form submit handler
    $('form').submit(function() {
        if ($('#tipe-subkriteria').val() === 'rentang nilai') {
            // Set nilai subkriteria_nama dari preview
            $('input[name="subkriteria_nama_numerik"]').val($('#preview-subkriteria').val());
        }
    });
    
    // Fungsi untuk mengubah tampilan berdasarkan operator
    function handleOperatorChange() {
        let operator = $('#operator').val();
        
        if (operator === 'between') {
            $('#nilai-minimum-container, #nilai-maksimum-container').show();
        } else if (operator === '<' || operator === '<=') {
            $('#nilai-minimum-container').hide();
            $('#nilai-maksimum-container').show();
        } else {
            $('#nilai-minimum-container').show();
            $('#nilai-maksimum-container').hide();
        }
        
        updatePreview();
    }
    
    // Fungsi untuk memperbarui preview rentang nilai
    function updatePreview() {
        let operator = $('#operator').val();
        let min = $('input[name="nilai_minimum"]').val();
        let max = $('input[name="nilai_maksimum"]').val();
        let preview = '';
        
        if (operator === 'between' && min && max) {
            preview = formatNumber(min) + ' - ' + formatNumber(max);
        } else if ((operator === '<' || operator === '<=') && max) {
            preview = operator + ' ' + formatNumber(max);
        } else if ((operator === '>' || operator === '>=') && min) {
            preview = operator + ' ' + formatNumber(min);
        }
        
        $('#preview-subkriteria').val(preview);
    }
    
    // Fungsi untuk memperbarui preview nilai angka
    function updateAngkaPreview() {
        let angka = $('#subkriteria_nilai_angka').val();
        let satuan = $('#subkriteria_satuan').val();
        let preview = '';
        
        if (angka) {
            preview = formatNumber(angka);
            if (satuan) {
                preview += ' ' + satuan;
            }
        }
        
        $('#preview-subkriteria-angka').val(preview);
    }
    
    // Fungsi untuk memformat angka
    function formatNumber(num) {
        if (!num) return '';
        return new Intl.NumberFormat('id-ID').format(num);
    }
    
    // Inisialisasi tampilan berdasarkan nilai yang dipilih
    handleOperatorChange();
    updateAngkaPreview();

    // Inisialisasi tampilan berdasarkan nilai old() jika ada error
    const oldTipeSubkriteria = $('#tipe-subkriteria').data('old');
if (oldTipeSubkriteria) {
    $('#tipe-subkriteria').val(oldTipeSubkriteria).trigger('change');
}

});

$(document).ready(function() {
    // Toggle tampilan form berdasarkan tipe subkriteria
    $('#tipe-subkriteria').change(function() {
        if ($(this).val() === 'rentang nilai') {
            $('#subkriteria-teks').hide();
            $('#subkriteria-angka').hide();
            $('#subkriteria-numerik').show();
            handleOperatorChange(); // Panggil fungsi untuk mengatur tampilan awal
        } else if ($(this).val() === 'angka') {
            $('#subkriteria-teks').hide();
            $('#subkriteria-angka').show();
            $('#subkriteria-numerik').hide();
            updateAngkaPreview();
        } else {
            $('#subkriteria-teks').show();
            $('#subkriteria-angka').hide();
            $('#subkriteria-numerik').hide();
        }
    });
    
    // Logika tampilan berdasarkan operator
    $('#operator').change(function() {
        handleOperatorChange();
    });
    
    // Update preview saat nilai berubah
    $('.nilai-numerik').on('input', function() {
        updatePreview();
    });
    
    // Update preview angka
    $('#subkriteria_nilai_angka, #subkriteria_satuan').on('input', function() {
        updateAngkaPreview();
    });
    
    // Form submit handler
    $('#form-edit').submit(function(e) {
        // Always set the hidden field for 'rentang nilai' type
        if ($('#tipe-subkriteria').val() === 'rentang nilai') {
            $('#subkriteria_nama_numerik').val($('#preview-subkriteria').val());
            console.log('Submitting with rentang nilai:', $('#preview-subkriteria').val());
        }
        
        // Logs for debugging
        console.log('Form submitted with type:', $('#tipe-subkriteria').val());
        
        // Allow the form to submit
        return true;
    });
    
    // Fungsi untuk mengubah tampilan berdasarkan operator
    function handleOperatorChange() {
        let operator = $('#operator').val();
        
        if (operator === 'between') {
            $('#nilai-minimum-container, #nilai-maksimum-container').show();
        } else if (operator === '<' || operator === '<=') {
            $('#nilai-minimum-container').hide();
            $('#nilai-maksimum-container').show();
        } else {
            $('#nilai-minimum-container').show();
            $('#nilai-maksimum-container').hide();
        }
        
        updatePreview();
    }
    
    // Fungsi untuk memperbarui preview rentang nilai
    function updatePreview() {
        let operator = $('#operator').val();
        let min = $('input[name="nilai_minimum"]').val();
        let max = $('input[name="nilai_maksimum"]').val();
        let preview = '';
        
        if (operator === 'between' && min && max) {
            preview = formatNumber(min) + ' - ' + formatNumber(max);
        } else if ((operator === '<' || operator === '<=') && max) {
            preview = operator + ' ' + formatNumber(max);
        } else if ((operator === '>' || operator === '>=') && min) {
            preview = operator + ' ' + formatNumber(min);
        }
        
        $('#preview-subkriteria').val(preview);
        $('#subkriteria_nama_numerik').val(preview);
    }
    
    // Fungsi untuk memperbarui preview nilai angka
    function updateAngkaPreview() {
        let angka = $('#subkriteria_nilai_angka').val();
        let satuan = $('#subkriteria_satuan').val();
        let preview = '';
        
        if (angka) {
            preview = formatNumber(angka);
            if (satuan) {
                preview += ' ' + satuan;
            }
        }
        
        $('#preview-subkriteria-angka').val(preview);
    }
    
    // Fungsi untuk memformat angka
    function formatNumber(num) {
        if (!num) return '';
        return new Intl.NumberFormat('id-ID').format(num);
    }
    
    // Inisialisasi tampilan pada saat halaman dimuat
    handleOperatorChange();
    updateAngkaPreview();
    updatePreview();
});