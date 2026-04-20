# 🍽️ MamiViet API - Reservation System Documentation

**Version:** 1.0
**Base URL:** `https://your-domain.com/api`
**Last Updated:** 2024-10-19

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Localization](#localization)
4. [Common Responses](#common-responses)
5. [Error Handling](#error-handling)
6. [User APIs (Public)](#user-apis-public)
7. [Admin APIs (Protected)](#admin-apis-protected)
8. [Data Models](#data-models)
9. [Business Rules](#business-rules)
10. [Code Examples](#code-examples)

---

## 🔍 Overview

The MamiViet Reservation API allows customers to book tables at the restaurant and provides administrative tools for managing reservations. The system supports:

- ✅ Table reservations with availability checking
- ✅ Multi-language support (German/English)
- ✅ Status management (pending → confirmed → completed)
- ✅ Customer self-service (lookup by email/phone)
- ✅ Administrative oversight and reporting

**Operating Hours:** 11:00 - 22:00 daily
**Max Party Size:** 20 persons
**Languages:** German (default), English

---

## 🔐 Authentication

### User APIs (Public)
Most reservation APIs are **public** and don't require authentication. Customers can create and manage reservations without accounts.

### Admin APIs (Protected)
Admin APIs require authentication headers:

```http
Authorization: Bearer {your-sanctum-token}
Content-Type: application/json
Accept: application/json
```

**Admin Login:**
```http
POST /api/admin/auth/login
{
    "email": "admin@example.com",
    "password": "password"
}
```

---

## 🌍 Localization

All APIs support multi-language responses via:

### Method 1: Query Parameter
```http
GET /api/user/reservations/1?locale=en
```

### Method 2: Header
```http
X-Locale: de
```

### Method 3: Form Data
```json
{
    "locale": "en",
    "name": "John Doe"
}
```

**Supported Languages:**
- `de` - German (default)
- `en` - English

---

## ✅ Common Responses

### Success Response
```json
{
    "success": true,
    "message": "Reservation created successfully. Staff will arrange a table for you.",
    "data": {
        "id": 1,
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+49 123 456789",
        "persons": 4,
        "date": "2024-12-25",
        "time": "19:00",
        "status": "pending",
        "status_label": "Ausstehend",
        "admin_notes": null,
        "created_at": "2024-10-19T10:30:00.000000Z",
        "updated_at": "2024-10-19T10:30:00.000000Z"
    }
}
```

### Paginated Response
```json
{
    "success": true,
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 45,
        "last_page": 3,
        "from": 1,
        "to": 15
    }
}
```

---

## ❌ Error Handling

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "time": ["Reservations are only available between 11:00 and 22:00."]
    }
}
```

### Not Found (404)
```json
{
    "success": false,
    "message": "Reservation not found."
}
```

### Unauthorized (401)
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
    "success": false,
    "message": "Forbidden"
}
```

---

## 👥 User APIs (Public)

Base URL: `/api/user/reservations`
Middleware: `setlocale` only (no auth required)

### 1. Create Reservation

**Endpoint:** `POST /api/user/reservations`

**Request Body:**
```json
{
    "name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+49 123 456789",
    "persons": 4,
    "date": "2024-12-25",
    "time": "19:00"
}
```

**Validation Rules:**
- `name`: required, string, max 255 chars
- `email`: required, valid email, max 255 chars
- `phone`: required, string, max 20 chars
- `persons`: required, integer, 1-20
- `date`: required, date, today or future
- `time`: required, HH:MM format, 11:00-22:00

**Response (201):**
```json
{
    "success": true,
    "message": "Reservierung erfolgreich erstellt. Das Personal wird einen Tisch für Sie arrangieren.",
    "data": { /* ReservationResource */ }
}
```

**cURL Example:**
```bash
curl -X POST "https://your-domain.com/api/user/reservations" \
  -H "Content-Type: application/json" \
  -H "X-Locale: de" \
  -d '{
    "name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+49 123 456789",
    "persons": 4,
    "date": "2024-12-25",
    "time": "19:00"
  }'
```

---

### 2. Get Reservation Details

**Endpoint:** `GET /api/user/reservations/{id}`

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Max Mustermann",
        "email": "max@example.com",
        "phone": "+49 123 456789",
        "persons": 4,
        "date": "2024-12-25",
        "time": "19:00",
        "status": "pending",
        "status_label": "Ausstehend",
        "admin_notes": null,
        "created_at": "2024-10-19T10:30:00.000000Z",
        "updated_at": "2024-10-19T10:30:00.000000Z"
    }
}
```

---

### 3. Update Reservation

**Endpoint:** `PUT /api/user/reservations/{id}`

⚠️ **Note:** Only works if reservation status is `pending`

**Request Body (partial update):**
```json
{
    "persons": 6,
    "time": "20:00"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Reservierung erfolgreich aktualisiert.",
    "data": { /* Updated ReservationResource */ }
}
```

**Error Response (422):**
```json
{
    "success": false,
    "message": "Diese Reservierung kann nicht mehr geändert werden."
}
```

---

### 4. Cancel Reservation

**Endpoint:** `POST /api/user/reservations/{id}/cancel`

⚠️ **Note:** Only works if status is `pending` or `confirmed`

**Response (200):**
```json
{
    "success": true,
    "message": "Reservierung erfolgreich storniert.",
    "data": { /* Updated ReservationResource with status: "cancelled" */ }
}
```

---

### 5. Get Reservations by Email

**Endpoint:** `GET /api/user/reservations/by-email/list`

**Query Parameters:**
- `email` (required): Email address
- `status` (optional): Filter by status
- `sort_by` (optional): `created_at`, `reservation_time`
- `sort_direction` (optional): `asc`, `desc`
- `per_page` (optional): Items per page (default: 10)

**Example Request:**
```http
GET /api/user/reservations/by-email/list?email=max@example.com&status=pending&per_page=5
```

**Response (200):**
```json
{
    "success": true,
    "data": [
        { /* ReservationResource */ },
        { /* ReservationResource */ }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 5,
        "total": 12,
        "last_page": 3,
        "from": 1,
        "to": 5
    }
}
```

---

### 6. Get Reservations by Phone

**Endpoint:** `GET /api/user/reservations/by-phone/list`

**Query Parameters:**
- `phone` (required): Phone number
- `status` (optional): Filter by status
- `per_page` (optional): Items per page (default: 10)

**Example Request:**
```http
GET /api/user/reservations/by-phone/list?phone=%2B49123456789&status=confirmed
```

---

### 7. Check Availability

**Endpoint:** `POST /api/user/reservations/check-availability`

**Request Body:**
```json
{
    "date": "2024-12-25",
    "time": "19:00",
    "persons": 4
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "available": true,
        "remaining_capacity": 46,
        "requested_persons": 4,
        "message": "Verfügbar für die gewünschte Zeit und Personenzahl."
    }
}
```

**No Availability Response:**
```json
{
    "success": true,
    "data": {
        "available": false,
        "remaining_capacity": 2,
        "requested_persons": 4,
        "message": "Nicht verfügbar. Verbleibende Kapazität: 2 Personen."
    }
}
```

---

## 🛡️ Admin APIs (Protected)

Base URL: `/api/admin/reservations`
Middleware: `['setlocale', 'auth:sanctum', 'admin']`

### 1. List All Reservations

**Endpoint:** `GET /api/admin/reservations`

**Query Parameters:**
- `status` (optional): Filter by status
- `date` (optional): Filter by specific date
- `date_from` (optional): Filter from date
- `date_to` (optional): Filter to date
- `search` (optional): Search name, email, phone
- `sort_by` (optional): `created_at`, `reservation_time`, `name`, `status`
- `sort_direction` (optional): `asc`, `desc`
- `per_page` (optional): Items per page (default: 15)

**Example Request:**
```http
GET /api/admin/reservations?status=pending&search=Max&sort_by=reservation_time&per_page=20
```

**Response (200):**
```json
{
    "success": true,
    "data": [ /* Array of ReservationResource */ ],
    "meta": { /* Pagination meta */ }
}
```

---

### 2. Create Reservation (Admin)

**Endpoint:** `POST /api/admin/reservations`

**Request Body:**
```json
{
    "name": "Max Mustermann",
    "email": "max@example.com",
    "phone": "+49 123 456789",
    "persons": 4,
    "date": "2024-12-25",
    "time": "19:00",
    "status": "confirmed",
    "admin_notes": "VIP customer, window table preferred"
}
```

**Additional Fields (Admin only):**
- `status` (optional): Set initial status
- `admin_notes` (optional): Admin notes

---

### 3. Get Reservation Details (Admin)

**Endpoint:** `GET /api/admin/reservations/{id}`

Same as user endpoint but includes admin-specific data.

---

### 4. Update Reservation (Admin)

**Endpoint:** `PUT /api/admin/reservations/{id}`

**Request Body (any fields):**
```json
{
    "status": "confirmed",
    "admin_notes": "Confirmed by phone call",
    "persons": 6
}
```

Admins can update any field at any time.

---

### 5. Delete Reservation

**Endpoint:** `DELETE /api/admin/reservations/{id}`

**Response (200):**
```json
{
    "success": true,
    "message": "Reservierung erfolgreich gelöscht."
}
```

---

### 6. Update Reservation Status

**Endpoint:** `PUT /api/admin/reservations/{id}/status`

**Request Body:**
```json
{
    "status": "confirmed",
    "admin_notes": "Confirmed by manager"
}
```

**Valid Status Transitions:**
- `pending` → `confirmed`, `cancelled`
- `confirmed` → `completed`, `cancelled`
- `cancelled` → (no transitions)
- `completed` → (no transitions)

---

### 7. Get Statistics

**Endpoint:** `GET /api/admin/reservations/statistics`

**Response (200):**
```json
{
    "success": true,
    "data": {
        "total": 150,
        "today": 12,
        "upcoming": 45,
        "by_status": {
            "pending": 25,
            "confirmed": 20,
            "cancelled": 5,
            "completed": 100
        }
    }
}
```

---

## 📊 Data Models

### Reservation Resource
```typescript
interface Reservation {
    id: number
    name: string
    email: string
    phone: string
    persons: number
    date: string          // YYYY-MM-DD
    time: string          // HH:MM
    status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
    status_label: string  // Translated status
    admin_notes: string | null
    created_at: string    // ISO 8601
    updated_at: string    // ISO 8601
}
```

### Availability Response
```typescript
interface AvailabilityCheck {
    available: boolean
    remaining_capacity: number
    requested_persons: number
    message: string
}
```

### Statistics Response
```typescript
interface ReservationStatistics {
    total: number
    today: number
    upcoming: number
    by_status: {
        pending: number
        confirmed: number
        cancelled: number
        completed: number
    }
}
```

---

## 📋 Business Rules

### 1. Operating Hours
- Reservations only accepted between **11:00 - 22:00**
- Validation enforced on both frontend and backend

### 2. Party Size
- Minimum: **1 person**
- Maximum: **20 persons**

### 3. Date Restrictions
- Reservations must be for **today or future dates**
- No past date bookings allowed

### 4. Status Workflow
```
pending → confirmed → completed
    ↓         ↓
cancelled  cancelled
```

### 5. User Permissions
- **Users (Public):** Can create, view, update (pending only), cancel (pending/confirmed)
- **Admins:** Full CRUD access, status management, reporting

### 6. Capacity Management
- Default capacity: **50 persons per time slot**
- Configurable in service layer
- Real-time availability checking

---

## 💻 Code Examples

### JavaScript/Fetch Examples

#### Create Reservation
```javascript
const createReservation = async (reservationData) => {
    try {
        const response = await fetch('/api/user/reservations', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Locale': 'de'
            },
            body: JSON.stringify(reservationData)
        })

        const result = await response.json()

        if (result.success) {
            console.log('Reservation created:', result.data)
            return result.data
        } else {
            console.error('Validation errors:', result.errors)
            throw new Error(result.message)
        }
    } catch (error) {
        console.error('Error:', error)
        throw error
    }
}

// Usage
createReservation({
    name: 'Max Mustermann',
    email: 'max@example.com',
    phone: '+49 123 456789',
    persons: 4,
    date: '2024-12-25',
    time: '19:00'
})
```

#### Check Availability
```javascript
const checkAvailability = async (date, time, persons) => {
    const response = await fetch('/api/user/reservations/check-availability', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Locale': 'de'
        },
        body: JSON.stringify({ date, time, persons })
    })

    const result = await response.json()
    return result.data
}

// Usage
const availability = await checkAvailability('2024-12-25', '19:00', 4)
if (availability.available) {
    console.log(`Available! ${availability.remaining_capacity} spots remaining`)
} else {
    console.log(`Not available. Only ${availability.remaining_capacity} spots left`)
}
```

#### Get User Reservations
```javascript
const getUserReservations = async (email, filters = {}) => {
    const params = new URLSearchParams({
        email,
        ...filters
    })

    const response = await fetch(`/api/user/reservations/by-email/list?${params}`, {
        headers: {
            'X-Locale': 'de'
        }
    })

    return await response.json()
}

// Usage
const reservations = await getUserReservations('max@example.com', {
    status: 'pending',
    per_page: 5
})
```

#### Admin: Get Statistics
```javascript
const getReservationStatistics = async (token) => {
    const response = await fetch('/api/admin/reservations/statistics', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json',
            'X-Locale': 'de'
        }
    })

    const result = await response.json()
    return result.data
}
```

### React Hook Example
```javascript
import { useState, useEffect } from 'react'

const useReservations = (email) => {
    const [reservations, setReservations] = useState([])
    const [loading, setLoading] = useState(true)
    const [error, setError] = useState(null)

    useEffect(() => {
        const fetchReservations = async () => {
            try {
                setLoading(true)
                const result = await getUserReservations(email)
                if (result.success) {
                    setReservations(result.data)
                } else {
                    setError(result.message)
                }
            } catch (err) {
                setError(err.message)
            } finally {
                setLoading(false)
            }
        }

        if (email) {
            fetchReservations()
        }
    }, [email])

    return { reservations, loading, error }
}
```

### Vue.js Composition API Example
```javascript
import { ref, computed } from 'vue'

export function useReservationForm() {
    const form = ref({
        name: '',
        email: '',
        phone: '',
        persons: 1,
        date: '',
        time: ''
    })

    const isValid = computed(() => {
        return form.value.name &&
               form.value.email &&
               form.value.phone &&
               form.value.date &&
               form.value.time &&
               form.value.persons >= 1
    })

    const submitReservation = async () => {
        if (!isValid.value) return

        try {
            const result = await createReservation(form.value)
            // Handle success
            return result
        } catch (error) {
            // Handle error
            throw error
        }
    }

    return {
        form,
        isValid,
        submitReservation
    }
}
```

---

## 🎯 Quick Start Guide

### 1. Create a Reservation
```bash
curl -X POST "/api/user/reservations" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","phone":"+49123456789","persons":2,"date":"2024-12-25","time":"19:00"}'
```

### 2. Check the Reservation
```bash
curl "/api/user/reservations/1"
```

### 3. Check Availability
```bash
curl -X POST "/api/user/reservations/check-availability" \
  -H "Content-Type: application/json" \
  -d '{"date":"2024-12-25","time":"19:00","persons":4}'
```

### 4. Get User's Reservations
```bash
curl "/api/user/reservations/by-email/list?email=test@example.com"
```

---

## 🐛 Common Issues & Solutions

### Issue 1: Time Validation Error
**Error:** "Reservations are only available between 11:00 and 22:00"
**Solution:** Ensure time is in HH:MM format and between 11:00-22:00

### Issue 2: Date Validation Error
**Error:** "The date must be today or in the future"
**Solution:** Use YYYY-MM-DD format and don't use past dates

### Issue 3: Cannot Update Reservation
**Error:** "This reservation can no longer be updated"
**Solution:** Users can only update reservations with status "pending"

### Issue 4: Availability Issues
**Error:** "Not available"
**Solution:** Check capacity limits or choose different time slots

### Issue 5: Localization Not Working
**Solution:** Include `X-Locale: de` header or `locale` parameter

---

## 📞 Support

For technical support or questions about this API:

- **Email:** tech@mamiviet.com
- **Documentation:** [Internal Wiki Link]
- **Postman Collection:** [Link to Postman Collection]

---

**Happy Coding! 🚀**