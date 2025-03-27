<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('logokacamata.png') }}">
    <title>SPK Frame Kacamata</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar">
        <div class="sidebar-header">
            <h4>SPK Frame Kacamata</h4>
        </div>
        <div class="sidebar-menu">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="{{ route('kriteria.index') }}" class="{{ request()->routeIs('kriteria.*') ? 'active' : '' }}">
                <i class="fas fa-cogs"></i> <span>Kriteria</span>
            </a>
            <a href="{{ route('subkriteria.index') }}" class="{{ request()->routeIs('subkriteria.*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i> <span>Subkriteria</span>
            </a>
            <a href="{{ route('frame.index') }}" class="{{ request()->routeIs('frame.*') ? 'active' : '' }}">
                <i class="fas fa-glasses"></i> <span>Frame</span>
            </a>
            <a href="{{ route('penilaian.index') }}" class="{{ request()->routeIs('penilaian.*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i> <span>Penilaian</span>
            </a>
            <a href="{{ route('rekomendasi.index') }}" class="{{ request()->routeIs('rekomendasi.*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i> <span>Riwayat Rekomendasi</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div id="content">
        <!-- Header -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" id="navbar-sidebar-toggle" href="#">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- User Dropdown Menu -->
                <li class="nav-item dropdown">
                    <a class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                        <i class="fas fa-user mr-2"></i> 
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                    
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <span class="dropdown-item dropdown-header">Profil</span>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('profile') }}" class="dropdown-item">
                            <i class="fas fa-user mr-2"></i> Lihat Profil
                        </a>
                        <div class="dropdown-divider"></div>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="button" onclick="confirmLogout()" class="dropdown-item">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div class="container mt-4">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <script>
        function confirmLogout() {
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
                    document.getElementById('logout-form').submit();
                }
            });
        }
    </script>

    @stack('scripts')
</body>
</html>