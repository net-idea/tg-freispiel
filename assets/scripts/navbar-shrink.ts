/**
 * Navbar shrink functionality
 * Adds 'navbar-shrink' class when user scrolls down
 */

window.addEventListener('DOMContentLoaded', () => {
  const navbar = document.querySelector('#main-nav');
  if (!navbar) {
    return;
  }

  const navbarShrink = () => {
    if (window.scrollY === 0) {
      navbar.classList.remove('navbar-shrink');
    } else {
      navbar.classList.add('navbar-shrink');
    }
  };

  // Initial check
  navbarShrink();

  // Listen for scroll events
  document.addEventListener('scroll', navbarShrink);
});
