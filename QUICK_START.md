# Quick Start Guide

## Setup

1. **Run Migrations** (if not already done):
   ```bash
   php artisan migrate
   ```

2. **Seed Super Admin** (if not already done):
   ```bash
   php artisan db:seed --class=SuperAdminSeeder
   ```

3. **Start Development Server**:
   ```bash
   php artisan serve
   ```

## Super Admin Login

**Email:** `superadmin@gmail.com`  
**Password:** `12345678`

## API Examples Using cURL

### 1. Login as Super Admin

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "superadmin@gmail.com",
    "password": "12345678"
  }'
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "superadmin@gmail.com",
    "role": "super_admin"
  },
  "token": "YOUR_API_TOKEN_HERE"
}
```

### 2. Create a Restaurant

```bash
curl -X POST http://localhost:8000/api/restaurants \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE" \
  -d '{
    "name": "Pizza Palace",
    "email": "pizza@example.com",
    "phone": "123-456-7890",
    "address": "123 Main Street, City, State"
  }'
```

**What happens:**
- Restaurant record is created
- User account is created with temporary password
- Welcome email is sent to the restaurant owner with login credentials

### 3. List All Restaurants

```bash
curl -X GET http://localhost:8000/api/restaurants \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE"
```

### 4. Get Specific Restaurant

```bash
curl -X GET http://localhost:8000/api/restaurants/1 \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE"
```

### 5. Update a Restaurant

```bash
curl -X PUT http://localhost:8000/api/restaurants/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE" \
  -d '{
    "name": "Updated Pizza Palace",
    "phone": "098-765-4321"
  }'
```

### 6. Delete a Restaurant

```bash
curl -X DELETE http://localhost:8000/api/restaurants/1 \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE"
```

### 7. Get Current User Profile

```bash
curl -X GET http://localhost:8000/api/me \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE"
```

### 8. Logout

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE"
```

## Testing

Run all tests:
```bash
php artisan test --compact
```

Run specific test file:
```bash
php artisan test tests/Feature/AuthApiTest.php --compact
```

Run specific test:
```bash
php artisan test --filter="user_can_login_with_valid_credentials" --compact
```

## File Organization

### Controllers
- `app/Http/Controllers/Api/AuthController.php` - Login, logout, profile
- `app/Http/Controllers/Api/RestaurantController.php` - Restaurant CRUD

### Business Logic
- `app/Services/AuthService.php` - Authentication logic
- `app/Services/RestaurantService.php` - Restaurant creation/updates

### Database Operations
- `app/Repositories/UserRepository.php` - User queries
- `app/Repositories/RestaurantRepository.php` - Restaurant queries

### Models
- `app/Models/User.php` - User model with role support
- `app/Models/Restaurant.php` - Restaurant model

### Routes
- `routes/api.php` - API routes

### Tests
- `tests/Feature/AuthApiTest.php` - Auth endpoint tests
- `tests/Feature/RestaurantApiTest.php` - Restaurant endpoint tests

## Validation Rules

### Create/Update Restaurant
- **name** - Required, string, max 255 chars
- **email** - Required for creation, unique, valid email
- **phone** - Optional, string, max 20 chars
- **address** - Optional, string, max 500 chars

### Login
- **email** - Required, valid email
- **password** - Required, string

## Response Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthorized (not logged in) |
| 403 | Forbidden (not super admin) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

## Key Features

✅ **Clean Architecture**
- Business logic in services
- Database operations in repositories
- Controllers handle HTTP only

✅ **Role-Based Access Control**
- Super admin can manage restaurants
- Restaurant users can only access their data
- Middleware-based authorization

✅ **Email Notifications**
- Welcome email sent when restaurant is created
- Contains temporary password and login instructions

✅ **Comprehensive Testing**
- 15+ test cases covering all scenarios
- Tests for authorization, validation, and business logic

✅ **Database Protection**
- Cascading deletes (deleting restaurant also deletes user)
- Email uniqueness validation
- Required field validation

✅ **Type Safety**
- Full type hints on all methods
- Enum for user roles
- Casting for role field

## Common Issues & Solutions

### "Email already exists"
Restaurant emails must be unique. Ensure you're using a new email when creating a restaurant.

### "Unauthorized. Super admin access required"
Make sure you're using a super admin token. Regular users cannot access restaurant endpoints.

### "The given data was invalid"
Check that all required fields are provided:
- `name` (required)
- `email` (required)
- `phone` (optional)
- `address` (optional)

### Tests not running
Make sure MySQL is running and `qrcode_test` database exists, or update `phpunit.xml` with your database credentials.

## Database Schema

### Users Table
```
id (PK)
name
email (UNIQUE)
password
role (enum: super_admin, restaurant, user)
timestamps
```

### Restaurants Table
```
id (PK)
user_id (FK)
name
email (UNIQUE)
phone (nullable)
address (nullable)
temporary_password (nullable)
timestamps
```

## Next Steps

1. ✅ Seed super admin user
2. ✅ Test login with curl
3. ✅ Create a restaurant
4. ✅ Test authorization on protected routes
5. ✅ Run test suite
6. ✅ Integrate with frontend

## Documentation

For complete API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

## Support Files

- `app/Enums/UserRole.php` - Role enumeration
- `app/Http/Middleware/EnsureSuperAdmin.php` - Authorization middleware
- `app/Mail/RestaurantCreated.php` - Email notification
- `resources/views/emails/restaurant-created.blade.php` - Email template
- `database/factories/RestaurantFactory.php` - Test data factory
- `database/seeders/SuperAdminSeeder.php` - Initial super admin seeder

## Form Requests

- `app/Http/Requests/LoginRequest.php` - Login validation
- `app/Http/Requests/StoreRestaurantRequest.php` - Restaurant creation validation
