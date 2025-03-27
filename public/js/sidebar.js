document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const navbarSidebarToggle = document.getElementById('navbar-sidebar-toggle');
    const navbarToggleIcon = navbarSidebarToggle.querySelector('i');
    
    // Function to toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        content.classList.toggle('expanded');
        
        // Ensure icon is always bars when toggling
        navbarToggleIcon.className = 'fas fa-bars';
    }
    
    // Add click event to navbar sidebar toggle
    navbarSidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    });
    
    // Handle responsive behavior
    function handleResize() {
        // Automatically collapse sidebar on mobile
        if (window.innerWidth <= 768) {
            if (!sidebar.classList.contains('collapsed')) {
                sidebar.classList.add('collapsed');
                content.classList.add('expanded');
                navbarToggleIcon.className = 'fas fa-bars';
            }
        }
    }
    
    // Check on initial load and window resize
    handleResize();
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