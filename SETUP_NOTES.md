# Setup Notes

## Project Structure Created

All the required directory structure, migrations, models, controllers, services, and routes have been created according to your project specification.

## Required Dependencies

To complete the setup, you need to install the following packages:

### 1. Laravel Sanctum (Required for API Authentication)
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

After installation, uncomment the `HasApiTokens` trait in `app/Models/User.php`:
```php
use Laravel\Sanctum\HasApiTokens;
// Then add to the use statement: use HasFactory, Notifiable, HasApiTokens, HasGeographicLocation;
```

### 2. Intervention Image (Optional - for image resizing)
```bash
composer require intervention/image
```

If not installed, the MediaService will still work but won't resize images.

## Environment Variables

Add these to your `.env` file:

```env
# Host Pinnacle SMS Service
HOST_PINNACLE_API_URL=https://api.hostpinnacle.com/sms
HOST_PINNACLE_API_KEY=your_api_key_here
HOST_PINNACLE_SENDER_ID=PARTY
```

## Database Setup

1. Run migrations:
```bash
php artisan migrate:fresh
```

2. Seed the database:
```bash
php artisan db:seed
```

Or run both together:
```bash
php artisan migrate:fresh --seed
```

This will:
- **Fetch and seed geographic data from GitHub** (Siaya and Nakuru counties with all constituencies and wards)
- Create default admin users:
  - Admin: Phone `254700000000` / Password `admin123`
  - Moderator: Phone `254700000001` / Password `moderator123`

**⚠️ IMPORTANT: Change default admin passwords in production!**

### Geographic Data Seeder

The `GeographicDataSeeder` automatically fetches data from:
- `https://raw.githubusercontent.com/stevehoober254/kenya-county-data/main/counties.json`
- `https://raw.githubusercontent.com/stevehoober254/kenya-county-data/main/constituencies.json`
- `https://raw.githubusercontent.com/stevehoober254/kenya-county-data/main/wards.json`

Currently seeds **Siaya** and **Nakuru** counties. To add more counties, edit `GeographicDataSeeder.php` and add to the `TARGET_COUNTIES` array.

To run just the geographic seeder:
```bash
php artisan db:seed --class=GeographicDataSeeder
```

## API Routes

All API routes are defined in `routes/api.php`:
- `/api/auth/*` - Authentication endpoints
- `/api/geographic/*` - Geographic data endpoints
- `/api/posts/*` - Post management
- `/api/posts/{id}/comments/*` - Comment management
- `/api/engagement/*` - Likes, shares, flags

## Admin Routes

Admin routes are defined in `routes/web.php`:
- `/admin/dashboard` - Dashboard metrics
- `/admin/moderation/*` - Content moderation
- `/admin/analytics/*` - Analytics and reports

## Next Steps

1. Install Laravel Sanctum (required)
2. Install Intervention Image (optional)
3. Configure Host Pinnacle SMS credentials in `.env`
4. Run migrations and seeders
5. Populate complete IEBC data in `IEBCDataSeeder.php`
6. Set up storage link: `php artisan storage:link`
7. Configure your storage driver (S3, local, etc.) in `config/filesystems.php`

## Notes

- The `ApiAuthentication` middleware will return an error if Sanctum is not installed
- The `MediaService` works without Intervention Image but won't resize images
- Geographic data seeder contains sample data - populate with complete IEBC data
- Admin authentication uses session-based auth (configure in `config/auth.php`)

