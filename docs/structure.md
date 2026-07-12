# Project Structure

Detailed overview of the tg-freispiel project layout. Generated directories (`vendor/`, `node_modules/`, `var/`, `public/build/`) are listed but not expanded.

```
tg-freispiel.de/
├── bin/
│   ├── console                   # Symfony console (local PHP)
│   ├── command                   # Run console commands in the Docker php service
│   ├── php                       # Run raw PHP in the Docker php service
│   ├── yarn                      # Run Yarn in the Docker node service
│   └── phpunit                   # PHPUnit bridge binary
├── assets/
│   ├── app.ts                    # TS entry (Webpack Encore)
│   ├── bootstrap.ts              # Stimulus bootstrap
│   ├── stimulus_bootstrap.ts     # Stimulus app startup
│   ├── controllers.json          # Stimulus bridge entry
│   ├── controllers/
│   │   ├── ajax_form_controller.ts        # AJAX form submission
│   │   └── csrf_protection_controller.js  # CSRF token handling
│   ├── scripts/
│   │   ├── contacts.ts           # Contact page behaviors
│   │   ├── navbar-shrink.ts      # Navbar shrink on scroll
│   │   └── theme-toggle.ts       # Light/dark theme handling
│   ├── styles/
│   │   ├── app.scss              # App styles
│   │   ├── fonts.scss            # Font faces
│   │   └── navbar.scss           # Navbar styles
│   ├── images/                   # Static images
│   └── types.d.ts                # Ambient TS declarations
├── config/                       # Symfony configuration (packages, routes, services)
├── docs/
│   ├── structure.md              # This file
│   ├── docker.md                 # Docker installation & usage
│   ├── symfony.md                # Symfony commands & troubleshooting
│   └── database.md               # Database troubleshooting
├── docker/                       # Container configs (nginx, php)
├── migrations/                   # Doctrine migrations
├── public/
│   ├── index.php                 # Front controller
│   └── build/                    # Compiled assets (generated)
├── src/
│   ├── Command/                  # Console commands (app:<domain>:<action>)
│   │   ├── AppSecretCommand.php
│   │   ├── ContactListCommand.php
│   │   ├── ContactMailPreviewCommand.php
│   │   ├── DateCreateCommand.php
│   │   ├── DateListCommand.php
│   │   ├── RegistrationListCommand.php
│   │   ├── UserCreateCommand.php
│   │   └── UserListCommand.php
│   ├── Controller/
│   │   ├── AbstractBaseController.php     # Shared controller helpers
│   │   ├── MainController.php             # Homepage & static pages
│   │   ├── ContactController.php          # Contact form page
│   │   ├── DateController.php             # Public dates (/termine)
│   │   └── RegistrationController.php     # Trial-session registration
│   ├── Dto/
│   │   ├── SubmissionResult.php           # Form submission outcome
│   │   └── SubmissionStatus.php           # Submission status enum
│   ├── Entity/
│   │   ├── DateEntity.php                 # Public dates
│   │   ├── FormBookingEntity.php          # Booking submissions
│   │   ├── FormContactEntity.php          # Contact submissions
│   │   ├── FormRegistrationEntity.php     # Registration submissions
│   │   ├── FormSubmissionMetaEntity.php   # Shared submission metadata
│   │   └── UserEntity.php                 # Users for the upcoming admin area
│   ├── Form/
│   │   ├── FormContactType.php
│   │   └── FormRegistrationType.php
│   ├── Repository/
│   │   ├── DateRepository.php
│   │   ├── FormContactRepository.php
│   │   ├── FormRegistrationRepository.php
│   │   └── UserRepository.php
│   ├── Service/
│   │   ├── AbstractFormService.php        # Shared form handling
│   │   ├── DateProviderService.php        # Upcoming dates provider
│   │   ├── FormContactService.php
│   │   ├── FormRegistrationService.php
│   │   ├── MailManService.php             # Mail composition & sending
│   │   └── NavigationService.php          # Navbar items
│   └── Kernel.php
├── templates/
│   ├── base.html.twig            # Base layout with theme support
│   ├── _partials/
│   │   ├── navbar.html.twig      # Navigation with theme switcher
│   │   ├── footer.html.twig
│   │   └── flash_messages.html.twig
│   ├── pages/
│   │   ├── index.html.twig       # Homepage
│   │   ├── contact.html.twig
│   │   ├── dates.html.twig       # /termine
│   │   ├── registration.html.twig
│   │   └── datenschutz.html.twig
│   └── email/                    # Owner/visitor mails (HTML + text variants)
├── tests/                        # PHPUnit tests
├── translations/
├── var/                          # Cache & logs (generated)
├── vendor/                       # Composer deps (generated)
├── node_modules/                 # Node deps (generated)
├── develop.sh                    # Local dev helper (installs deps, builds, runs dev services)
├── deploy.sh                     # Production deploy helper
├── docker-start.sh / docker-stop.sh / docker-*.sh   # Docker stack helpers
├── docker-compose*.yml|yaml      # Compose files (base, dev, DB, debug tools)
├── database*.sh                  # DB init/migrate/backup helpers
├── lint.sh / phpunit.sh / php-cs-fixer.sh / pipeline.sh   # Quality & CI helpers
├── composer.json
├── package.json
└── readme.md
```
