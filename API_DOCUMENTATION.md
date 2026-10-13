# Restaurant Management API Documentation

## Overview

This Laravel API provides a complete restaurant management system with role-based authentication. The application follows clean architecture principles with separation of concerns using Services, Repositories, and Controllers.

## Architecture Layers

### 1. **Controllers** (`app/Http/Controllers/Api/`)
- Handle HTTP requests/responses
- Route parameters to services
- Return JSON responses

### 2. **Services** (`app/Services/`)
- Contain all business logic
- Coordinate between repositories and other services
- Handle data transformation and validation
- Send notifications (emails)

### 3. **Repositories** (`app/Repositories/`)
- Handle all database operations
- Encapsulate Eloquent queries
- Provide clean interfaces to services

### 4. **Models** (`app/Models/`)
- Define database schema relationships
- Include casting and helper methods

## Database Structure

### Users Table
Stores all users with role-based access:
- `id` - Primary key
- `name` - User name
- `email` - Unique email address
- `password` - Hashed password
- `role` - User role (super_admin, restaurant, user)
- `timestamps` - Created and updated timestamps

### Restaurants Table
Stores restaurant details:
- `id` - Primary key
- `user_id` - Foreign key to users table (one-to-one)
- `name` - Restaurant name (required)
- `email` - Restaurant email (required, unique)
- `phone` - Contact phone (optional)
- `address` - Physical address (optional)
- `temporary_password` - Initial password sent to restaurant owner
- `timestamps` - Created and updated timestamps

## User Roles

### Super Admin
- Can create, update, and delete restaurants
- Can view all restaurants
- Full API access
- **Default Credentials:**
  - Email: `superadmin@gmail.com`
  - Password: `12345678`

### Restaurant
- Can only access their own restaurant data
- Can log in with their email and temporary password
- Should change password on first login

### User
- Regular user role
- Can view their own profile

## API Endpoints

### Authentication Endpoints

#### Login
```
POST /api/login
Content-Type: application/json

{
    "email": "superadmin@gmail.com",
    "password": "12345678"
}

Response: 200 OK
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "Super Admin",
        "email": "superadmin@gmail.com",
        "role": "super_admin"
    },
    "token": "api-token-here"
}
```

#### Logout
```
POST /api/logout
Authorization: Bearer {token}

Response: 200 OK
{
    "message": "Logged out successfully"
}
```

#### Get Current User Profile
```
GET /api/me
Authorization: Bearer {token}

Response: 200 OK
{
    "user": {
        "id": 1,
        "name": "Super Admin",
        "email": "superadmin@gmail.com",
        "role": "super_admin"
    }
}
```

### Restaurant Management Endpoints (Super Admin Only)

All restaurant endpoints require super admin authentication. Responses will return `403 Forbidden` for non-super admin users.

#### List All Restaurants
```
GET /api/restaurants
Authorization: Bearer {token}

Response: 200 OK
{
    "data": [
        {
            "id": 1,
            "name": "Pizza Palace",
            "email": "pizza@example.com",
            "phone": "123-456-7890",
            "address": "123 Main St",
            "created_at": "2026-09-09T10:30:00Z"
        }
    ]
}
```

#### Create Restaurant
```
POST /api/restaurants
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Pizza Palace",
    "email": "pizza@example.com",
    "phone": "123-456-7890",
    "address": "123 Main St"
}

Response: 201 Created
{
    "message": "Restaurant created successfully",
    "data": {
        "id": 1,
        "name": "Pizza Palace",
        "email": "pizza@example.com",
        "phone": "123-456-7890",
        "address": "123 Main St",
        "created_at": "2026-09-09T10:30:00Z"
    }
}
```

**Note:** When a restaurant is created:
1. A user account is automatically created with the restaurant's email
2. A temporary password is generated
3. A welcome email is sent to the restaurant owner with their login credentials

#### Get Specific Restaurant
```
GET /api/restaurants/{id}
Authorization: Bearer {token}

Response: 200 OK
{
    "data": {
        "id": 1,
        "name": "Pizza Palace",
        "email": "pizza@example.com",
        "phone": "123-456-7890",
        "address": "123 Main St",
        "user_id": 2,
        "created_at": "2026-09-09T10:30:00Z",
        "updated_at": "2026-09-09T10:30:00Z"
    }
}
```

#### Update Restaurant
```
PUT /api/restaurants/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Pizza Palace Updated",
    "phone": "098-765-4321"
}

Response: 200 OK
{
    "message": "Restaurant updated successfully",
    "data": {
        "id": 1,
        "name": "Pizza Palace Updated",
        "email": "pizza@example.com",
        "phone": "098-765-4321",
        "address": "123 Main St",
        "updated_at": "2026-09-09T11:00:00Z"
    }
}
```

#### Delete Restaurant
```
DELETE /api/restaurants/{id}
Authorization: Bearer {token}

Response: 200 OK
{
    "message": "Restaurant deleted successfully"
}
```

**Note:** Deleting a restaurant also deletes the associated user account.

## Validation Rules

### Create/Update Restaurant
- `name` - Required, string, max 255 characters
- `email` - Required (for creation), must be unique across restaurants, valid email format
- `phone` - Optional, string, max 20 characters
- `address` - Optional, string, max 500 characters

### Login
- `email` - Required, must be valid email format
- `password` - Required, string

## Error Responses

### 401 Unauthorized
```json
{
    "message": "Unauthenticated."
}
```

### 403 Forbidden (Super Admin Only)
```json
{
    "message": "Unauthorized. Super admin access required."
}
```

### 422 Validation Error
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "name": ["The name field is required."]
    }
}
```

### 404 Not Found
```json
{
    "message": "Not found."
}
```

## Email Notifications

### Restaurant Created Email
When a restaurant is created, an email is sent to the restaurant owner containing:
- Congratulations message
- Login email address
- Temporary password
- Portal login link
- Instructions to change password

**Email Template:** `resources/views/emails/restaurant-created.blade.php`

## File Structure

```
app/
├── Enums/
│   └── UserRole.php                 # User role enumeration
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php       # Authentication endpoints
│   │   └── RestaurantController.php # Restaurant CRUD endpoints
│   ├── Middleware/
│   │   └── EnsureSuperAdmin.php     # Super admin validation middleware
│   └── Requests/
│       ├── LoginRequest.php         # Login validation
│       └── StoreRestaurantRequest.php # Restaurant creation validation
├── Mail/
│   └── RestaurantCreated.php        # Restaurant welcome email
├── Models/
│   ├── User.php                     # User model with role casting
│   └── Restaurant.php               # Restaurant model with relationships
├── Repositories/
│   ├── UserRepository.php           # User database operations
│   └── RestaurantRepository.php     # Restaurant database operations
└── Services/
    ├── AuthService.php              # Authentication business logic
    └── RestaurantService.php        # Restaurant business logic

database/
├── migrations/
│   ├── 2026_09_09_222341_add_role_to_users_table.php
│   └── 2026_09_09_222346_create_restaurants_table.php
├── factories/
│   ├── UserFactory.php
│   └── RestaurantFactory.php
└── seeders/
    └── SuperAdminSeeder.php         # Seeds initial super admin

routes/
└── api.php                          # API routes definition

tests/
└── Feature/
    ├── AuthApiTest.php              # Authentication tests
    └── RestaurantApiTest.php        # Restaurant endpoints tests

resources/
└── views/emails/
    └── restaurant-created.blade.php # Welcome email template
```

## Getting Started

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
php artisan migrate
php artisan db:seed --class=SuperAdminSeeder
```

### 4. Run Development Server
```bash
php artisan serve
```

### 5. Run Tests
```bash
php artisan test
```

## Testing

Comprehensive test suites are included in `tests/Feature/`:

### AuthApiTest
- User login with valid credentials
- Login failure with invalid credentials
- Super admin login
- User logout
- Authenticated user profile retrieval
- Unauthenticated user protection

### RestaurantApiTest
- Create restaurant (super admin only)
- View all restaurants
- View specific restaurant
- Update restaurant
- Delete restaurant
- Authorization checks
- Validation rules
- Email notification
- Email uniqueness

Run tests with:
```bash
php artisan test --compact
```

## Code Standards

### PHP Code Style
- Uses Laravel Pint for code formatting
- Follows PSR-12 coding standard
- Type hints on all method parameters and returns
- PHPDoc blocks for complex logic

Run formatting:
```bash
vendor/bin/pint
```

## Database Seeders

### SuperAdminSeeder
Creates initial super admin user:
- Email: `superadmin@gmail.com`
- Password: `12345678`
- Role: `super_admin`

Run seeder:
```bash
php artisan db:seed --class=SuperAdminSeeder
```

## Middleware

### EnsureSuperAdmin
Validates that the authenticated user is a super admin:
- Applied to all restaurant management endpoints
- Returns 403 Forbidden for non-super admin users

## Service Layer Examples

### AuthService
```php
// Login user
$user = $authService->login('email@example.com', 'password');

// Register new user
$user = $authService->register([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'password123'
]);
```

### RestaurantService
```php
// Create restaurant
$restaurant = $restaurantService->createRestaurant([
    'name' => 'Pizza Palace',
    'email' => 'pizza@example.com',
    'phone' => '123-456-7890',
    'address' => '123 Main St'
]);

// Update restaurant
$restaurant = $restaurantService->updateRestaurant($restaurant, [
    'name' => 'Updated Name'
]);

// Delete restaurant
$restaurantService->deleteRestaurant($restaurant);
```

## Best Practices Implemented

1. **Separation of Concerns** - Business logic in services, data access in repositories
2. **Dependency Injection** - Services injected into controllers
3. **Type Hints** - All methods have proper type declarations
4. **Validation** - Form requests and service-level validation
5. **Error Handling** - Proper HTTP status codes and error messages
6. **Testing** - Comprehensive feature tests for all endpoints
7. **Clean Code** - Meaningful variable names, single responsibility
8. **Email Notifications** - Automatic welcome email on restaurant creation
9. **Role-Based Access Control** - Middleware-based authorization
10. **Factory Pattern** - Model factories for testing

## Future Enhancements

- Add restaurant categories
- Implement menu management
- Add order tracking
- Implement restaurant analytics
- Add customer reviews and ratings
- Implement subscription plans
- Add API rate limiting
- Implement audit logging
