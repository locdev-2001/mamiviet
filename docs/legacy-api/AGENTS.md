# Agent Guidelines for MamiViet API

## Build/Lint/Test Commands
- **Install PHP deps**: `composer install`
- **Install JS deps**: `npm install`
- **Run all tests**: `./vendor/bin/phpunit`
- **Run single test**: `./vendor/bin/phpunit tests/Feature/ExampleTest.php` or `./vendor/bin/phpunit tests/Unit/ExampleTest.php`
- **Code formatting**: `./vendor/bin/pint`
- **Dev server**: `php artisan serve`
- **Frontend dev**: `npm run dev`
- **Frontend build**: `npm run build`

## Code Style Guidelines
- **PSR-4 autoloading** with `App\` namespace
- **4-space indentation**, UTF-8, LF line endings
- **Imports**: Group by type (classes, then traits/interfaces), alphabetical within groups
- **Naming**: PascalCase for classes, camelCase for methods/properties, snake_case for DB columns
- **Architecture**: Service layer pattern with thin controllers delegating to services
- **Models**: Define `$fillable`, `$hidden`, `$casts` arrays; use relationships methods
- **Controllers**: Constructor injection for services, return API Resources
- **Error handling**: Use Laravel's exception handling and validation
- **Security**: Never expose sensitive data, use proper authentication middleware