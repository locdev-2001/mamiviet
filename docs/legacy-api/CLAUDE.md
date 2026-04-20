# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel-based restaurant management API system called "MamiViet API" that handles restaurant operations including menu management, user authentication, cart functionality, order processing, coupon management, and a lucky wheel reward system. The system serves both customer-facing and admin functionalities.

## Common Development Commands

### Backend (Laravel/PHP)
- **Install dependencies**: `composer install`
- **Run tests**: `./vendor/bin/phpunit` or `php artisan test`
- **Run specific test**: `./vendor/bin/phpunit tests/Feature/ExampleTest.php`
- **Code formatting**: `./vendor/bin/pint` (Laravel Pint for code style)
- **Start development server**: `php artisan serve`
- **Run migrations**: `php artisan migrate`
- **Seed database**: `php artisan db:seed`
- **Clear caches**: `php artisan cache:clear`, `php artisan config:clear`, `php artisan route:clear`
- **Generate app key**: `php artisan key:generate`

### Frontend Assets (Vite)
- **Install JS dependencies**: `npm install` or `yarn install`
- **Development build**: `npm run dev` or `yarn dev`
- **Production build**: `npm run build` or `yarn build`

### Database
- **Create migration**: `php artisan make:migration create_table_name`
- **Create model**: `php artisan make:model ModelName -m` (with migration)
- **Create factory**: `php artisan make:factory ModelFactory`
- **Create seeder**: `php artisan make:seeder TableSeeder`

## Architecture & Code Structure

### Authentication System
- **Dual authentication**: Separate User and Admin models with Laravel Sanctum for API tokens
- **User authentication**: Uses `App\Models\User` with standard Laravel auth
- **Admin authentication**: Uses `App\Models\Admin` with custom `AdminAuthService` and `AdminMiddleware`
- **Admin middleware**: Checks for `role === 'admin'` on authenticated users
- **API tokens**: Generated via Sanctum for both users and admins

### Service Layer Architecture
The application follows a service-oriented architecture pattern:
- **Auth Services**: `AdminAuthService`, `UserAuthService`, `UserProfileService`
- **Business Logic Services**: `CartService`, `AdminMenuService`, `AdminCouponService`, etc.
- **Controllers**: Thin controllers that delegate to services for business logic
- **Resources**: API resource classes for consistent JSON responses

### API Structure
- **User APIs**: Prefixed with `/user` - handles customer-facing operations
- **Admin APIs**: Prefixed with `/admin` - requires admin authentication middleware
- **Middleware chain for admin**: `['setlocale', 'auth:sanctum', 'admin']`
- **Localization**: `SetLocale` middleware for multi-language support (English/German)

### Key Models & Relationships
- **User**: Standard customer model with cart relationships
- **Admin**: Separate admin user model
- **MenuItem**: Menu items with categories and media library integration
- **MenuCategory**: Hierarchical menu organization with position sorting
- **Coupon**: Discount coupon system with user assignments
- **Reward**: Lucky wheel rewards system
- **UserCoupon**: Pivot table for user-coupon relationships

### Third-party Packages
- **Laravel Sanctum**: API authentication
- **Spatie Media Library**: File/image management for menu items
- **Laravel Cart packages**: `binafy/laravel-cart` and `darryldecode/cart` for shopping cart functionality

### Request Validation
- **Form Requests**: Organized in `app/Http/Requests/` with separate admin and user request classes
- **Naming convention**: `AdminStore*Request`, `AdminUpdate*Request`, `User*Request`

### API Resources
- **Consistent responses**: Uses Laravel API Resources for standardized JSON output
- **Located in**: `app/Http/Resources/`

### Database Considerations
- **Migration files**: Well-organized with timestamps showing development progression
- **Foreign keys**: Proper relationships between users, menu items, categories, and coupons
- **Media table**: Integrated with Spatie Media Library for file management

### Testing Setup
- **PHPUnit**: Standard Laravel testing with Feature and Unit test directories
- **Environment**: Configured for array drivers during testing to avoid external dependencies

## Development Guidelines

### When working with authentication:
- Users and Admins are completely separate entities with different models
- Always use appropriate middleware chains for admin routes
- Admin operations require both `auth:sanctum` and `admin` middleware

### When working with menu items:
- Menu items use Spatie Media Library for image management
- Categories have position-based sorting capabilities
- Check existing services before implementing new menu logic

### When working with cart functionality:
- Multiple cart packages are integrated - check existing CartService implementation
- Cart operations are user-specific and require authentication

### When working with API responses:
- Use existing API Resource classes for consistent response formats
- Follow the established pattern in existing controllers

### Code Style:
- Follow Laravel conventions and PSR standards
- Use Laravel Pint for code formatting: `./vendor/bin/pint`
- Service classes handle business logic, controllers remain thin