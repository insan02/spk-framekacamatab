document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePasswordBtn = document.getElementById('toggle-password');
    const loginForm = document.querySelector('form');

    // Password visibility toggle
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function() {
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
        loginForm.addEventListener('submit', function(event) {
            const emailInput = document.getElementById('email');
            let isValid = true;

            // Reset previous error states
            emailInput.classList.remove('is-invalid');
            passwordInput.classList.remove('is-invalid');

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.value.trim())) {
                emailInput.classList.add('is-invalid');
                isValid = false;
            }

            // Password validation
            if (passwordInput.value.trim() === '') {
                passwordInput.classList.add('is-invalid');
                isValid = false;
            }

            // Prevent form submission if validation fails
            if (!isValid) {
                event.preventDefault();
            }
        });
    }

    // Optional: Client-side error message handling
    function showErrorMessage(message) {
        Swal.fire({
            icon: 'error',
            title: 'Login Error',
            text: message,
            showConfirmButton: true
        });
    }

    // Optional: Client-side error handling (if using AJAX or want to intercept form submission)
    // Note: This should be adapted based on your specific backend implementation
    function handleLoginError(error) {
        if (error.response) {
            // The request was made and the server responded with a status code
            // that falls out of the range of 2xx
            showErrorMessage(error.response.data.message || 'Login failed. Please try again.');
        } else if (error.request) {
            // The request was made but no response was received
            showErrorMessage('No response from server. Please check your connection.');
        } else {
            // Something happened in setting up the request that triggered an Error
            showErrorMessage('An unexpected error occurred.');
        }
    }
});