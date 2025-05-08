<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SPK Frame Kacamata</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <div class="container">
        @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Menampilkan pesan error token expired -->
        @error('token')
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $message }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @enderror

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-container">
                    <div class="login-header">
                        <h3><i class="fas fa-glasses me-2"></i>SPK Frame Kacamata</h3>
                        <p class="text-center">Reset Password</p>
                    </div>
                    <div class="login-body">
                        <form method="POST" action="{{ route('password.reset.update') }}">
                            @csrf
                            <!-- Hidden token field -->
                            <input type="hidden" name="token" value="{{ $token }}">
                            
                            <!-- Hidden email field to prevent tampering -->
                            <input type="hidden" name="email" value="{{ $email ?? old('email') }}">
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <div class="form-control bg-light">{{ $email ?? old('email') }}</div>
                                @error('email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password Baru
                                </label>
                                <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required 
                                       autocomplete="new-password">
                                <button class="btn btn-outline-secondary" 
                                       type="button" 
                                       id="toggle-password">
                                   <i class="fas fa-eye-slash"></i>
                               </button>
                                </div>
                                <small class="form-text text-muted">
                                    Password minimal 8 karakter, harus mengandung huruf besar, huruf kecil, dan angka
                                </small>
                                @error('password')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="password-strength-meter mt-2">
                                    <div class="password-strength-meter-fill"></div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Konfirmasi Password
                                </label>
                                <div class="input-group">
                                <input type="password" 
                                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required>
                                <button class="btn btn-outline-secondary" 
                                       type="button" 
                                       id="toggle-confirmation-password">
                                   <i class="fas fa-eye-slash"></i>
                               </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                
                            </div>
                            

                            <div class="d-grid">
                                <button type="submit" class="btn btn-login text-white">
                                    <i class="fas fa-key me-2"></i>Reset Password
                                </button>
                            </div>
                            
                            <div class="mt-3 text-center">
                                <a href="{{ route('login') }}" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Kembali ke Login
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <!-- Custom Login JS -->
    <script src="{{ asset('js/auth.js') }}"></script>
</body>
</html>