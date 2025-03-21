document.addEventListener('DOMContentLoaded', function() {
    // Make navbar sidebar toggle work in sync with the main sidebar toggle
    const navbarSidebarToggle = document.getElementById('navbar-sidebar-toggle');
    const mainSidebarToggle = document.getElementById('sidebar-toggle');
    
    if (navbarSidebarToggle && mainSidebarToggle) {
        navbarSidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            // Trigger the click on the main sidebar toggle
            mainSidebarToggle.click();
        });
    }
    
    // Bootstrap 5 dropdown initialization (not needed with BS5 as it's auto-initialized)
    // This is just for additional functionality if needed
    
    // Function to handle logout confirmation
    window.showLogoutPopup = function() {
        if (confirm('Apakah Anda yakin ingin keluar?')) {
            document.getElementById('logout-form').submit();
        }
        return false;
    };
});