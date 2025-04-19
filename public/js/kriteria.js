document.addEventListener('DOMContentLoaded', function () {
    console.log('Script kriteria.js dimuat'); // Debugging

    // Menampilkan notifikasi sukses dari session Laravel
    const successMessageElement = document.querySelector('[data-success-message]');
    if (successMessageElement) {
        const successMessage = successMessageElement.getAttribute('data-success-message');
        if (successMessage) {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: successMessage,
                showConfirmButton: false,
                timer: 2000
            });
        }
    }

    // Menampilkan notifikasi error dari session Laravel
    const errorMessageElement = document.querySelector('[data-error-message]');
    if (errorMessageElement) {
        const errorMessage = errorMessageElement.getAttribute('data-error-message');
        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan',
                text: errorMessage,
                showConfirmButton: true
            });
        }
    }


    // Validasi sebelum submit pada form tambah dan edit kriteria
    const formEdit = document.getElementById("form-edit");

    // Di dalam kriteria.js
if (formEdit) {
    formEdit.addEventListener("submit", function (event) {
        event.preventDefault(); // Mencegah submit langsung

        Swal.fire({
            title: "Yakin ingin mengedit data?",
            text: "Data akan diperbarui!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Ya, perbarui!",
            cancelButtonText: "Batal"
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Form submit setelah konfirmasi'); // Tambahkan log
                formEdit.removeEventListener('submit', arguments.callee); // Hapus listener ini
                formEdit.submit(); // Submit form setelah konfirmasi
            }
        });
    });
}

    // Konfirmasi sebelum menghapus data
    const deleteForms = document.querySelectorAll('form[method="POST"] button[type="submit"].btn-danger');
    deleteForms.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Anda tidak dapat mengembalikan data yang dihapus!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

// Fungsi untuk menampilkan pesan error di bawah input
function showValidationError(input, message) {
    let errorElement = input.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('invalid-feedback')) {
        errorElement = document.createElement('div');
        errorElement.classList.add('invalid-feedback');
        input.parentNode.appendChild(errorElement);
    }
    errorElement.innerHTML = `<strong>${message}</strong>`;
    errorElement.style.display = 'block';
}

// Fungsi untuk menyembunyikan error
function hideValidationError(input) {
    let errorElement = input.nextElementSibling;
    if (errorElement && errorElement.classList.contains('invalid-feedback')) {
        errorElement.style.display = 'none';
    }
}
