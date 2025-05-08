document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('toggle-password');
    const loginForm = document.querySelector('form');
    const logoutForm = document.getElementById('logout-form');
    const resetPasswordForm = document.getElementById('resetPasswordForm');

    // Password visibility toggle
    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function () {
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    }

    // Form validation
    if (loginForm) {
        loginForm.addEventListener('submit', function (event) {
            const emailInput = document.getElementById('email');
            let isValid = true;

            if (emailInput) {
                emailInput.classList.remove('is-invalid');
                if (passwordInput) passwordInput.classList.remove('is-invalid');

                // Updated email regex to specifically require @gmail.com
                const emailRegex = /^[^\s@]+@gmail\.com$/;
                if (!emailRegex.test(emailInput.value.trim())) {
                    emailInput.classList.add('is-invalid');
                    // Create or get feedback element
                    let feedback = emailInput.nextElementSibling;
                    if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                        feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        emailInput.parentNode.insertBefore(feedback, emailInput.nextSibling);
                    }
                    feedback.textContent = 'Alamat email harus menggunakan @gmail.com';
                    isValid = false;
                }

                if (passwordInput && passwordInput.value.trim() === '') {
                    passwordInput.classList.add('is-invalid');
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault();
                }
            }
        });
    }

    

    // Apply password validation to reset password form
    if (resetPasswordForm) {
        const newPassword = document.getElementById('password');
        const confirmPassword = document.getElementById('password_confirmation');
        
        if (newPassword) {
            newPassword.addEventListener('input', function() {
                validatePassword(newPassword);
            });
        }
        
        if (confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                if (newPassword.value) {
                    validatePassword(newPassword, confirmPassword);
                }
            });
        }
        
        resetPasswordForm.addEventListener('submit', function(event) {
            // Tambahkan kode untuk reset loading state
            const button = document.getElementById('submitBtn');
            const spinner = document.getElementById('loadingSpinner');
            const sendIcon = document.getElementById('sendIcon');
            const buttonText = document.getElementById('buttonText');
            
            // Pastikan semua elemen ada
            if (button && spinner && sendIcon && buttonText) {
                const newPassword = document.getElementById('password');
                const confirmPassword = document.getElementById('password_confirmation');
                const emailInput = document.getElementById('email');
                let isValid = true;
                
                // Validasi password jika ada di form
                if (newPassword && confirmPassword) {
                    isValid = validatePassword(newPassword, confirmPassword);
                }
                
                // Validasi email
                if (emailInput && emailInput.type !== 'hidden' && !emailInput.readOnly) {
                    const emailRegex = /^[^\s@]+@gmail\.com$/;
                    if (!emailRegex.test(emailInput.value.trim())) {
                        emailInput.classList.add('is-invalid');
                        let feedback = emailInput.nextElementSibling;
                        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                            feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback';
                            emailInput.parentNode.insertBefore(feedback, emailInput.nextSibling);
                        }
                        feedback.textContent = 'Alamat email harus menggunakan @gmail.com';
                        isValid = false;
                    } else {
                        emailInput.classList.remove('is-invalid');
                    }
                }
                
                if (!isValid) {
                    event.preventDefault();
                } else {
                    // Tampilkan loading state
                    spinner.style.display = 'inline-block';
                    sendIcon.style.display = 'none';
                    buttonText.textContent = 'Mengirim...';
                    button.classList.add('btn-loading');
                    button.disabled = true;
                }
            }
        });
    }

    // Konfirmasi Logout
    if (logoutForm) {
        document.getElementById('logout-button').addEventListener('click', function (event) {
            event.preventDefault(); // Mencegah submit langsung

            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    logoutForm.submit();
                }
            });
        });
    }
});