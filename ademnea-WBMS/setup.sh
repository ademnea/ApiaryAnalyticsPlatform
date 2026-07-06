#!/bin/bash
# =============================================================================
# AdEMNEA Beehive Monitoring System — Project Skeleton Setup
# Run this AFTER: composer create-project laravel/laravel ademnea
# Then: cd ademnea && bash setup.sh
# =============================================================================

echo "Creating AdEMNEA directory structure..."

# --- Controllers ---
mkdir -p app/Http/Controllers/Admin
mkdir -p app/Http/Controllers/Api/Farmer
mkdir -p app/Http/Controllers/Api/IoT

# --- Models ---
mkdir -p app/Models

# --- Services ---
mkdir -p app/Services

# --- Requests (Form Validation) ---
mkdir -p app/Http/Requests/Admin
mkdir -p app/Http/Requests/Api

# --- Notifications ---
mkdir -p app/Notifications

# --- Jobs (Queued) ---
mkdir -p app/Jobs

# --- Middleware ---
mkdir -p app/Http/Middleware

# --- Database ---
mkdir -p database/migrations
mkdir -p database/seeders
mkdir -p database/factories

# --- Resources / Views ---
mkdir -p resources/views/auth
mkdir -p resources/views/layouts
mkdir -p resources/views/admin/dashboard
mkdir -p resources/views/admin/users
mkdir -p resources/views/admin/roles
mkdir -p resources/views/admin/apiary
mkdir -p resources/views/admin/hives
mkdir -p resources/views/admin/devices
mkdir -p resources/views/admin/monitoring
mkdir -p resources/views/admin/anomaly
mkdir -p resources/views/admin/farmers
mkdir -p resources/views/admin/newsletter
mkdir -p resources/views/admin/publications
mkdir -p resources/views/admin/events
mkdir -p resources/views/admin/gallery
mkdir -p resources/views/admin/scholarship
mkdir -p resources/views/admin/feedback
mkdir -p resources/views/admin/workpackages
mkdir -p resources/views/admin/team
mkdir -p resources/views/admin/reports
mkdir -p resources/views/website
mkdir -p resources/views/errors

# --- Public Assets ---
mkdir -p public/css
mkdir -p public/js
mkdir -p public/images/gallery
mkdir -p public/images/newsletters
mkdir -p public/images/events
mkdir -p public/images/team
mkdir -p public/scholarship
mkdir -p public/publications

# --- ML / Python Modules ---
mkdir -p MODULES/ml_scripts
mkdir -p MODULES/ml_models
mkdir -p MODULES/report_scripts

# --- Routes ---
# routes/web.php and routes/api.php already exist in Laravel
# We will overwrite them with our stubs

echo "✓ Directory structure created."
echo ""
echo "Next steps:"
echo "  1. Copy migration files into database/migrations/"
echo "  2. Copy seeder files into database/seeders/"
echo "  3. Copy view files into resources/views/"
echo "  4. Copy controller stubs into app/Http/Controllers/"
echo "  5. Run: php artisan migrate --seed"
echo "  6. Run: php artisan serve"