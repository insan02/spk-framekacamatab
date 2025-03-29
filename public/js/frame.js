document.addEventListener('DOMContentLoaded', function() {
    const hargaInputs = document.querySelectorAll('input[name="frame_harga"]');
    
    hargaInputs.forEach(function(hargaInput) {
        // Format input on typing
        hargaInput.addEventListener('input', function(e) {
            // Remove non-numeric characters
            let value = this.value.replace(/[^\d]/g, '');
            
            // Format with thousands separator
            this.value = value ? 'Rp ' + value.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
        });

        // Prepare for form submission
        const form = hargaInput.closest('form');
        form.addEventListener('submit', function(e) {
            // Remove 'Rp' and '.' to submit a clean numeric value
            hargaInput.value = hargaInput.value.replace(/[^\d]/g, '');
        });

        // Ensure initial value is formatted correctly
        const initialValue = hargaInput.value.replace(/[^\d]/g, '');
        hargaInput.value = initialValue ? 'Rp ' + initialValue.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    });
});