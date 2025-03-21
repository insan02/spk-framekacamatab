document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const navbarSidebarToggle = document.getElementById('navbar-sidebar-toggle');
    const navbarToggleIcon = navbarSidebarToggle.querySelector('i');

    // Check if sidebar should be collapsed initially
    const isInitiallyCollapsed = window.innerWidth <= 768;
    
    // Set initial state
    navbarToggleIcon.className = 'fas fa-bars';


    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
        
        // Toggle icon
        navbarToggleIcon.className = 'fas fa-bars';

    }

    navbarSidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    });

    // Handle responsive behavior
    function handleResize() {
        // Only force collapsed state on mobile
        if (window.innerWidth <= 768 && !sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('collapsed');
            content.classList.add('expanded');
            navbarToggleIcon.className = 'fas fa-bars';
        }
    }

    // Check on window resize
    window.addEventListener('resize', handleResize);

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            const isClickInside = sidebar.contains(event.target) || 
                                navbarSidebarToggle.contains(event.target);
            
            if (!isClickInside && !sidebar.classList.contains('collapsed')) {
                toggleSidebar();
            }
        }
    });
});