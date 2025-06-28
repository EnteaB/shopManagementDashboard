document.addEventListener('DOMContentLoaded', function() {
    const menuBtn = document.querySelector('.menu-btn');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    // Toggle sidebar
    menuBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('shifted');
        
        // Store menu state
        const isMenuOpen = sidebar.classList.contains('active');
        localStorage.setItem('menuOpen', isMenuOpen);
    });

    // Restore menu state
    const menuOpen = localStorage.getItem('menuOpen') === 'true';
    if (menuOpen) {
        sidebar.classList.add('active');
        mainContent.classList.add('shifted');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (!sidebar.contains(e.target) && 
            !menuBtn.contains(e.target) && 
            window.innerWidth < 768) {
            sidebar.classList.remove('active');
            mainContent.classList.remove('shifted');
            localStorage.setItem('menuOpen', false);
        }
    });
});