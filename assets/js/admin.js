document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.admin-nav-toggle');
    const navLinks = document.querySelector('.admin-nav-links');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
});