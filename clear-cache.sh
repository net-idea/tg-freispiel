#!/bin/bash

# Clear all caches and prepare for testing
# Run this after making changes to forms or CSRF configuration

echo "🧹 Clearing all caches..."
rm -rf var/cache/*
php bin/console cache:clear --no-warmup --env=dev
php bin/console cache:clear --no-warmup --env=test 2>/dev/null || true

echo ""
echo "✅ Cache cleared successfully!"
echo ""
echo "🎉 You can now test the contact form at:"
echo "   http://localhost:8000/kontakt"
echo ""
echo "💡 Tips:"
echo "   - Do a hard refresh in browser: CMD+SHIFT+R (Mac) or CTRL+SHIFT+R (Windows/Linux)"
echo "   - Check that CSRF token field exists: form_contact[_token]"
echo "   - Run tests: ./phpunit.sh"
