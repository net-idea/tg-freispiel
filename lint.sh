#!/bin/bash

# Lint all files
yarn run lint:fix

# Type check TypeScript files
yarn run tsc:check

# Lint Twig files
php bin/console lint:twig templates

./php-cs-fixer.sh
