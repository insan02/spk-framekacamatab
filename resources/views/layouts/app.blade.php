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

    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/loading.css') }}">

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
            
            @if(false)
            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> <span>Pelanggan</span>
            </a>
            @endif

            <a href="{{ route('penilaian.index') }}" class="{{ request()->routeIs('penilaian.*') || request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-check"></i> <span>Penilaian</span>
            </a>

            @if(auth()->user()->role === 'owner')
            <a href="{{ route('employees.index') }}" class="{{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <i class="fas fa-user-tie"></i> <span>Karyawan</span>
            </a>
            @endif
        
            <a href="{{ route('rekomendasi.index') }}" class="{{ request()->routeIs('rekomendasi.*') ? 'active' : '' }}">
                <i class="fas fa-history"></i> <span>Riwayat Rekomendasi</span>
            </a>

            <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i> <span>Log</span>
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
                        
                        <form id="logout-form" method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="button" id="logout-button" class="dropdown-item">
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

    <div id="loading-overlay">
        <svg class="glasses-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="6" cy="12" r="4"></circle>
            <circle cx="18" cy="12" r="4"></circle>
            <line x1="10" y1="12" x2="14" y2="12"></line>
            <line x1="2" y1="12" x2="2" y2="12"></line>
            <line x1="22" y1="12" x2="22" y2="12"></line>
        </svg>
        <p>Loading...</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/sidebar.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    <script src="{{ asset('js/kriteria.js') }}"></script>
    <script src="{{ asset('js/frame.js') }}"></script>
    <script src="{{ asset('js/searchframe.js') }}"></script>

    @stack('scripts')
</body>
</html>