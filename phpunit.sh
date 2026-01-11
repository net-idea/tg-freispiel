#!/bin/bash

set -euo pipefail

# Run PHPUnit and filter the non-actionable "OK, but there were issues!" banner that can be
# caused by third-party deprecations (PHP 8.5 / vendor code) even when tests pass.
./vendor/bin/phpunit tests \
  | sed -e '/^OK, but there were issues!$/d' \
        -e '/^Tests: .*PHPUnit Notices: [0-9]\{1,\}\.$/d'
