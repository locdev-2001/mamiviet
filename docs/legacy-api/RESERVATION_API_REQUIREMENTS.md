# RESERVATION API REQUIREMENTS - LARAVEL BACKEND (SIMPLIFIED)

## Overview
Simple API for storing table reservation information for staff management. Operating hours: 11:00 - 22:00.

## Database Schema

### Table: `reservations`
```sql
CREATE TABLE reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    persons INT NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    special_requests TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_reservation_date (date),
    INDEX idx_reservation_time (time)
);
```

## API Endpoint

### Create Reservation (Only API Needed)
**POST** `/api/reservations`

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
    "name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+49 123 456789",
    "persons": 4,
    "date": "2024-12-25",
    "time": "19:00",
    "special_requests": "Vegetarische Optionen bitte"
}
```

**Validation Rules:**
```php
'name' => 'required|string|max:255',
'email' => 'required|email|max:255',
'phone' => 'required|string|max:20',
'persons' => 'required|integer|min:1|max:20',
'date' => 'required|date|after_or_equal:today',
'time' => 'required|date_format:H:i|after_or_equal:11:00|before_or_equal:22:00',
'special_requests' => 'nullable|string|max:1000'
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Reservation saved successfully. Staff will arrange table for you.",
    "data": {
        "id": 1,
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+49 123 456789",
        "persons": 4,
        "date": "2024-12-25",
        "time": "19:00:00",
        "special_requests": "Vegetarische Optionen bitte",
        "created_at": "2024-10-19T10:30:00.000000Z"
    }
}
```

**Error Response (422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "date": ["The date must be a date after or equal to today."]
    }
}
```


## Laravel Implementation

### Model: `app/Models/Reservation.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'persons',
        'date',
        'time',
        'special_requests'
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i'
    ];
}
```

### Controller: `app/Http/Controllers/ReservationController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'persons' => 'required|integer|min:1|max:20',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'special_requests' => 'nullable|string|max:1000'
        ]);

        // Custom validation for operating hours (11:00 - 22:00)
        $validator->after(function ($validator) use ($request) {
            if ($request->has('time')) {
                $time = $request->get('time');
                $hour = (int) explode(':', $time)[0];

                if ($hour < 11 || $hour > 22) {
                    $validator->errors()->add('time', 'Reservation time must be between 11:00 and 22:00');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reservation = Reservation::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Reservation saved successfully. Staff will arrange table for you.',
                'data' => $reservation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save reservation'
            ], 500);
        }
    }
}
```

### Routes: `routes/api.php`
```php
<?php

use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

Route::post('/reservations', [ReservationController::class, 'store']);
```

### Migration: `database/migrations/create_reservations_table.php`
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20);
            $table->integer('persons');
            $table->date('date');
            $table->time('time');
            $table->text('special_requests')->nullable();
            $table->timestamps();

            $table->index(['date', 'time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
};
```

## Business Rules

1. **Operating Hours**: 11:00 - 22:00 daily

2. **Maximum Party Size**: 20 persons

3. **Purpose**: Simple data collection for staff to arrange tables

## Deployment Notes

1. Run migration: `php artisan migrate`
2. Configure CORS in `config/cors.php`
3. Test endpoint with Postman

## Testing

### Example cURL Command
```bash
curl -X POST http://your-api.com/api/reservations \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "phone": "+49 123 456789",
    "persons": 2,
    "date": "2024-12-25",
    "time": "19:00",
    "special_requests": "Window table please"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Reservation saved successfully. Staff will arrange table for you.",
  "data": { ... }
}
```

---

## Summary

**Simple 1-API solution:**
- ✅ Single POST endpoint to save reservation data
- ✅ Operating hours: 11:00-22:00
- ✅ Staff can view/manage reservations in database
- ✅ No complex booking logic needed