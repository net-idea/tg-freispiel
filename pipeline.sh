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
BLUE='\033[1;34m'
NC='\033[0m' # No Color

# Configuration - safe defaults
CONTINUE_ON_ERROR=false
VERBOSE=false
PHP_VERSION='unknown'
NODE_VERSION='unknown'

# detect versions safely
if command -v php > /dev/null 2>&1; then
  PHP_VERSION=$(php -r "echo PHP_VERSION;")
fi

if command -v node > /dev/null 2>&1; then
  NODE_VERSION=$(node --version)
fi

# Collect failed checks (each entry: "Description|Reproduction command")
FAILED_CHECKS=()

# Parse arguments (POSIX-friendly)
while [[ $# -gt 0 ]]; do
  case "$1" in
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
    *)
      echo "Warning: Unknown option: $1"
      shift
      ;;
  esac
done

print_header() {
  echo ""
  echo -e "${BLUE}=====================================${NC}"
  echo -e "${BLUE}$1${NC}"
  echo -e "${BLUE}=====================================${NC}"
  echo ""
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
  echo -e "${YELLOW}⚠︎${NC} $1"
}

print_info() {
  echo -e "${BLUE}ⓘ${NC} $1"
}

handle_error() {
  if [ "$CONTINUE_ON_ERROR" = true ]; then
    print_warning "Error occurred, but continuing to next step."
    return 0
  fi

  print_error "Error occurred. Exiting."
  exit 1
}

run_command() {
  local description="$1"
  shift
  local -a cmd=("$@")

  print_step "$description"
  echo -e "${YELLOW}Command:${NC} ${cmd[*]}"

  if [ "$VERBOSE" = true ]; then
    if "${cmd[@]}"; then
      print_success "$description - OK"
      return 0
    else
      print_error "$description - FAILED"
      local cmd_str
      cmd_str=$(printf "%s " "${cmd[@]}")
      FAILED_CHECKS+=("$description|$cmd_str")
      return 1
    fi
  else
    if "${cmd[@]}" > /dev/null 2>&1; then
      print_success "$description - OK"
      return 0
    else
      print_error "$description - FAILED"
      local cmd_str
      cmd_str=$(printf "%s " "${cmd[@]}")
      FAILED_CHECKS+=("$description|$cmd_str")
      return 1
    fi
  fi
}

# helper to print failed checks with reproduction commands
print_failed_checks() {
  if [ ${#FAILED_CHECKS[@]} -eq 0 ]; then
    return 0
  fi

  print_header "Failed checks and how to reproduce them"

  for entry in "${FAILED_CHECKS[@]}"; do
    IFS='|' read -r name cmd <<<"$entry"
    echo -e "→ ${RED}${name}${NC}"
    echo -e "↳ ${YELLOW}Command:${NC} ${cmd}"
  done

  echo ""
}

run_php_cs_fixer_check() {
  if ! run_command "Run PHP CS Fixer check" php ./vendor/bin/php-cs-fixer check -n --config=.php-cs-fixer.dist.php; then
    print_info "Hint: Run './php-cs-fixer.sh' to auto-fix issues"

    # Prompt only when interactive
    if [ -t 0 ]; then
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
    else
      print_info "Non-interactive shell - skipping auto-fix prompt"
    fi

    return 1
  fi

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

  # 4. PHPStan static analysis for src
  if ! run_command "Run PHPStan static analysis (src only)" php -d memory_limit=-1 ./vendor/bin/phpstan analyze src; then
    ((failed_checks++))
    handle_error
  fi

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

  # ============================================
  # Code Formatting Check
  # ============================================

  print_header "Code Formatting Check"

  # 11. Prettier check
  # Run Prettier only on frontend assets and templates; exclude PHP to use PHP CS Fixer instead
  if ! run_command "Check code formatting with Prettier" npx prettier --check "**/*.{js,ts,tsx,css,scss,html,twig,json,md}"; then
    ((failed_checks++))
    print_warning "Hint: Run 'npx prettier --write .' or './format.sh' to auto-fix issues"
    handle_error
  fi

  # ============================================
  # Summary
  # ============================================

  print_header "Pipeline Summary"

  local end_time
  end_time=$(date +%s)
  local duration=$((end_time - start_time))

  if [ $failed_checks -eq 0 ]; then
    print_success "All checks passed! ✨"
    echo ""
    echo -e "${GREEN}Duration: ${duration}s${NC}"
    echo ""
    echo "Your code is ready to be pushed! 🚀"
    exit 0
  else
    print_error "$failed_checks check(s) failed"
    echo
    echo -e "${RED}Duration: ${duration}s${NC}"

    # Print a friendly summary listing failing checks and reproduction commands
    print_failed_checks

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
