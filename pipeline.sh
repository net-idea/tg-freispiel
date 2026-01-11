#!/usr/bin/env bash

# set -euo pipefail
# Removed 'set -e' to allow error handling and continuation
set -u
set -o pipefail

# Theatergruppe Freispiel CI/CD Pipeline - Local Execution
# This script runs all checks from GitHub Actions workflows locally
# Exit on first error unless --continue-on-error is specified

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
CONTINUE_ON_ERROR=false
VERBOSE=false
PHP_VERSION=$(php -r "echo PHP_VERSION;")
NODE_VERSION=$(node --version)

# Parse arguments
for arg in "$@"; do
  case $arg in
    --continue-on-error)
      CONTINUE_ON_ERROR=true
      shift
      ;;
    --verbose|-v)
      VERBOSE=true
      shift
      ;;
    --help|-h)
      echo "Usage: $0 [OPTIONS]"
      echo ""
      echo "Options:"
      echo "  --continue-on-error  Continue running checks even if one fails"
      echo "  --verbose, -v        Show detailed output"
      echo "  --help, -h           Show this help message"
      echo ""
      echo "This script runs all CI/CD checks locally:"
      echo "  - PHP Quality Checks (Composer, CS Fixer, PHPStan, PHPUnit)"
      echo "  - Node Quality Checks (TypeScript, Stylelint, Build)"
      echo "  - Code Formatting (Prettier)"
      echo "  - Template Linting (Twig)"
      exit 0
      ;;
  esac
done

# Helper functions
print_header() {
  echo -e "${BLUE}=====================================${NC}"
  echo -e "${BLUE}$1${NC}"
  echo -e "${BLUE}=====================================${NC}"
}

print_step() {
  echo -e "${GREEN}▶${NC} $1"
}

print_error() {
  echo -e "${RED}✗${NC} $1"
}

print_success() {
  echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
  echo -e "${YELLOW}⚠${NC} $1"
}

run_command() {
  local description=$1
  shift
  local cmd=("$@")

  print_step "$description"
  echo -e "${YELLOW}Command:${NC} ${cmd[*]}"

  if $VERBOSE; then
    if "${cmd[@]}"; then
      print_success "$description - OK"
      return 0
    else
      print_error "$description - FAILED"
      return 1
    fi
  else
    if "${cmd[@]}" > /dev/null 2>&1; then
      print_success "$description - OK"
      return 0
    else
      print_error "$description - FAILED"
      return 1
    fi
  fi
}

run_php_cs_fixer_check() {
  if ! run_command "Run PHP CS Fixer check" php ./vendor/bin/php-cs-fixer check -n --config=.php-cs-fixer.dist.php; then
    print_warning "Hint: Run './php-cs-fixer.sh' to auto-fix issues"

    while true; do
      read -p "Do you want to auto-fix with PHP CS Fixer? (y/n): " yn

      case $yn in
        [Yy]*)
          print_step "Running PHP CS Fixer auto-fix"
          php ./vendor/bin/php-cs-fixer fix -n --config=.php-cs-fixer.dist.php
          break
          ;;
        [Nn]*)
          break
          ;;
        *)
          echo "Please answer y or n."
          ;;
      esac
    done

    return 1
  fi

  return 0
}

handle_error() {
  print_warning "Error occurred, but continuing to next step."
  return 0
}

# Main pipeline
main() {
  local start_time
  start_time=$(date +%s)
  local failed_checks=0

  print_header "Theatergruppe Freispiel CI/CD Pipeline"
  echo "PHP Version: $PHP_VERSION"
  echo "Node Version: $NODE_VERSION"
  echo ""

  # ============================================
  # PHP Quality Checks
  # ============================================
  print_header "PHP Quality Checks"

  # 1. Validate composer.json
  if ! run_command "Validate composer.json and composer.lock" composer validate --strict; then
    ((failed_checks++))
    handle_error
  fi

  # 2. Install Composer dependencies
  if ! run_command "Install Composer dependencies" composer install --no-progress --no-interaction --prefer-dist --optimize-autoloader; then
    ((failed_checks++))
    handle_error
  fi

  # 3. PHP CS Fixer check
  if ! run_php_cs_fixer_check; then
    ((failed_checks++))
    handle_error
  fi

  # 4. PHPStan static analysis
  if ! run_command "Run PHPStan static analysis" php -d memory_limit=-1 ./vendor/bin/phpstan analyze src; then
    ((failed_checks++))
    handle_error
  fi

  if ! run_command "Run PHPStan static analysis" php -d memory_limit=-1 ./vendor/bin/phpstan analyze tests; then
    ((failed_checks++))
    handle_error
  fi

  echo ""

  # ============================================
  # Node/Yarn Quality Checks (for asset building)
  # ============================================
  print_header "Node/Yarn Quality Checks"

  # 5. Install Yarn dependencies
  if ! run_command "Install Yarn dependencies" yarn install --immutable; then
    ((failed_checks++))
    handle_error
  fi

  # 6. TypeScript type checking
  if ! run_command "TypeScript type checking" npx tsc --noEmit; then
    ((failed_checks++))
    handle_error
  fi

  # 7. Stylelint CSS/SCSS
  if ! run_command "Lint CSS/SCSS files" npx stylelint 'assets/**/*.{css,scss}'; then
    ((failed_checks++))
    print_warning "Hint: Run 'yarn run lint:css:fix' to auto-fix issues"
    handle_error
  fi

  # 8. Build assets (required for PHPUnit tests)
  if ! run_command "Build assets with Webpack Encore" yarn run build; then
    ((failed_checks++))
    handle_error
  fi

  echo ""

  # ============================================
  # PHP Tests (requires built assets)
  # ============================================
  print_header "PHP Tests"

  # 9. PHPUnit tests
  if ! run_command "Run PHPUnit tests" php ./vendor/bin/phpunit tests; then
    ((failed_checks++))
    handle_error
  fi

  # 10. Lint Twig templates
  if ! run_command "Lint Twig templates" php bin/console lint:twig templates; then
    ((failed_checks++))
    handle_error
  fi

  echo ""

  # ============================================
  # Code Formatting Check
  # ============================================
  print_header "Code Formatting Check"

  # 11. Prettier check
  if ! run_command "Check code formatting with Prettier" npx prettier --check .; then
    ((failed_checks++))
    print_warning "Hint: Run 'npx prettier --write .' or './format.sh' to auto-fix issues"
    handle_error
  fi

  echo ""

  # ============================================
  # Summary
  # ============================================
  local end_time
  end_time=$(date +%s)
  local duration=$((end_time - start_time))

  print_header "Pipeline Summary"

  if [ $failed_checks -eq 0 ]; then
    print_success "All checks passed! ✨"
    echo -e "${GREEN}Duration: ${duration}s${NC}"
    echo ""
    echo "Your code is ready to be pushed! 🚀"
    exit 0
  else
    print_error "$failed_checks check(s) failed"
    echo -e "${RED}Duration: ${duration}s${NC}"
    echo ""
    echo "Please fix the issues above before pushing."
    echo ""
    echo "Quick fixes:"
    echo "  - PHP CS Fixer:  ./php-cs-fixer.sh"
    echo "  - Stylelint:     yarn run lint:css:fix"
    echo "  - Prettier:      npx prettier --write ."
    echo "  - All formats:   ./format.sh"
    exit 1
  fi
}

# Run the pipeline
main "$@"
