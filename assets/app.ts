/*
 * Welcome to your app's main TypeScript entry file!
 *
 * We recommend including the built version of this file
 * (and its CSS) in your base layout (base.html.twig).
 */

// Import self-hosted web fonts first (Montserrat, Roboto Slab)
import './styles/fonts.scss';

// Main styles based and Bootstrap
import './styles/app.scss';
import './styles/navbar.scss';

// Font Awesome (local)
import '@fortawesome/fontawesome-free/css/all.min.css';

// Import Bootstrap JavaScript (local bundle with Popper)
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Start the Stimulus application (TypeScript)
import './bootstrap';

// Import Navbar functionality (TypeScript)
import './scripts/contacts.ts';
import './scripts/contact-form.ts';
import './scripts/navbar-shrink.ts';
import './scripts/theme-toggle.ts';

// Turbo removed
//import '@hotwired/turbo';
