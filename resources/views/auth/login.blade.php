<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('logokacamata.png') }}">
    <title>Login - SPK Frame Kacamata</title>
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
        <div class="row justify-content-center align-items-center min-vh-100">
            <!-- Flow Diagram -->
            <div class="col-lg-6 col-md-12 mb-4 mb-lg-0">
                <div class="flow-diagram">
                    <div class="flow-diagram-header">
                        <i class="fas fa-sitemap me-2"></i>ALUR PENGGUNAAN SISTEM
                    </div>
                    
                    <!-- First Row: Steps 1-4 -->
                    <div class="flow-row">
                        <!-- Step 1 -->
                        <div class="flow-step">
                            <div class="step-number">01</div>
                            <div class="step-icon">
                                <i class="fas fa-sign-in-alt"></i>
                            </div>
                            <div class="step-title">Login</div>
                        </div>
                        
                        <!-- Arrow -->
                        <div class="arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        
                        <!-- Step 2 -->
                        <div class="flow-step">
                            <div class="step-number">02</div>
                            <div class="step-icon">
                                <i class="fas fa-list"></i>
                            </div>
                            <div class="step-title">Tambah Kriteria</div>
                        </div>
                        
                        <!-- Arrow -->
                        <div class="arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        
                        <!-- Step 3 -->
                        <div class="flow-step">
                            <div class="step-number">03</div>
                            <div class="step-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="step-title">Tambah Subkriteria</div>
                        </div>
                        
                        <!-- Arrow -->
                        <div class="arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        
                        <!-- Step 4 -->
                        <div class="flow-step">
                            <div class="step-number">04</div>
                            <div class="step-icon">
                                <i class="fas fa-glasses"></i>
                            </div>
                            <div class="step-title">Tambah Alternatif</div>
                            <!-- Added vertical arrow from step 4 to step 5 -->
                            <div class="vertical-arrow">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Second Row: Steps 7-5 with arrows in reverse -->
                    <div class="flow-row mt-4">
                        <!-- Step 7 -->
                        <div class="flow-step">
                            <div class="step-number">07</div>
                            <div class="step-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="step-title">Hasil Rekomendasi</div>
                        </div>
                        
                        <!-- Arrow -->
                        <div class="arrow">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        
                        <!-- Step 6 -->
                        <div class="flow-step">
                            <div class="step-number">06</div>
                            <div class="step-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="step-title">Penilaian</div>
                        </div>
                        
                        <!-- Arrow -->
                        <div class="arrow">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        
                        <!-- Step 5 -->
                        <div class="flow-step">
                            <div class="step-number">05</div>
                            <div class="step-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="step-title">Pilih Pelanggan</div>
                        </div>
                        
                        <!-- Empty space to balance the layout -->
                        <div class="arrow" style="visibility: hidden;">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Login Form -->
            <div class="col-lg-4 col-md-6">
                <div class="login-container">
                    <div class="login-header">
                        <h3><i class="fas fa-glasses me-2"></i>SPK Frame Kacamata</h3>
                        <p class="mb-0 small">Toko Kacamata Sidi Pingai Bukittinggi</p>
                    </div>
                    <div class="login-body">
                        @if(session('status') || session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') ?? session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif
                        
                        <form method="POST" action="{{ route('login.submit') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required 
                                       placeholder="Masukkan email Gmail Anda @gmail.com">
                                <small class="form-text text-muted">Gunakan alamat email @gmail.com</small>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           placeholder="Masukkan password Anda"
                                           autocomplete="current-password">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="toggle-password">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-login text-white">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                            <div class="text-center">
                                <a href="{{ route('password.request') }}" class="forgot-password">
                                    <i class="fas fa-key me-1"></i>Lupa Password?
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