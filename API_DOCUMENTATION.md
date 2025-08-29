# Marine.ng API Documentation

## Base URL
```
Development: http://localhost:8000/api/v1
Production: https://api.marine.ng/api/v1
```

**Note:** All endpoints are prefixed with `/api/v1`

## Authentication
All protected endpoints require Bearer token authentication:
```
Authorization: Bearer {your_access_token}
```

## Response Format
All API responses follow this standard format:

**Success Response:**
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {...}
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Error message",
    "error": "Detailed error description"
}
```

---

## Authentication Endpoints

### 1. Register User
**POST** `/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "full_name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "company_name": "Marine Solutions Ltd",
    "phone": "+234801234567",
    "city": "Lagos",
    "state": "Lagos",
    "country": "Nigeria"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "profile": {
                "full_name": "John Doe",
                "company_name": "Marine Solutions Ltd",
                "phone": "+234801234567",
                "city": "Lagos",
                "state": "Lagos",
                "country": "Nigeria",
                "role": "user"
            }
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### 2. Login User
**POST** `/auth/login`

Authenticate user and get access token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "profile": {...}
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### 3. Logout User
**POST** `/auth/logout`

Logout user and revoke access token.

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### 4. Get Authenticated User
**GET** `/auth/user`

Get current authenticated user information.

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "profile": {...}
    }
}
```

### 5. Refresh Token
**POST** `/auth/refresh`

Refresh the current access token.

**Headers:** `Authorization: Bearer {token}`

**Success Response (200):**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "user": {...},
        "token": "1|new_token...",
        "token_type": "Bearer"
    }
}
```

### 6. Forgot Password
**POST** `/auth/forgot-password`

Send password reset link to email.

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

### 7. Reset Password
**POST** `/auth/reset-password`

Reset password using reset token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "token": "reset_token",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

### 8. Verify Email
**POST** `/auth/verify-email`

Verify user email address.

**Request Body:**
```json
{
    "id": 1,
    "hash": "verification_hash"
}
```

---

## Equipment Endpoints

### 1. Get Equipment Listings
**GET** `/equipment`

Get paginated list of equipment listings with optional filters.

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 20, max: 100)
- `category_id` (integer): Filter by category ID
- `condition` (string): Filter by condition (new, used, excellent, good, fair)
- `price_min` (decimal): Minimum price filter
- `price_max` (decimal): Maximum price filter
- `brand` (string): Filter by brand
- `state` (string): Filter by state/location
- `city` (string): Filter by city
- `featured_only` (boolean): Show only featured listings
- `verified_only` (boolean): Show only verified sellers
- `sort_by` (string): Sort field (created_at, price, views, title)
- `sort_direction` (string): Sort direction (asc, desc)
- `search` (string): Search in title and description

**Example Request:**
```
GET /api/equipment?category_id=1&condition=new&price_max=1000000&sort_by=price&sort_direction=asc&page=1&per_page=20
```

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Honda 40HP Outboard Motor",
            "description": "Excellent condition marine engine...",
            "price": 3200000.00,
            "currency": "NGN",
            "condition": "excellent",
            "brand": "Honda",
            "model": "BF40",
            "year": 2020,
            "location_city": "Lagos",
            "location_state": "Lagos",
            "location_country": "Nigeria",
            "is_featured": true,
            "is_verified": true,
            "view_count": 234,
            "created_at": "2025-01-15T10:30:00Z",
            "category": {
                "id": 1,
                "name": "Engines",
                "slug": "engines"
            },
            "user": {
                "id": 2,
                "name": "Marine Dealer",
                "email": "dealer@example.com"
            },
            "equipment_images": [
                {
                    "id": 1,
                    "image_url": "https://example.com/image1.jpg",
                    "is_primary": true,
                    "alt_text": "Honda engine front view"
                }
            ]
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "per_page": 20,
        "to": 20,
        "total": 100
    }
}
```

### 2. Get Single Equipment
**GET** `/equipment/{id}`

Get detailed information about a specific equipment listing.

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Honda 40HP Outboard Motor",
        "description": "Detailed description...",
        "specifications": {
            "power": "40HP",
            "fuel_type": "Gasoline",
            "weight": "85kg"
        },
        "price": 3200000.00,
        "currency": "NGN",
        "condition": "excellent",
        "brand": "Honda",
        "model": "BF40",
        "year": 2020,
        "location_city": "Lagos",
        "location_state": "Lagos",
        "contact_name": "John Marine",
        "contact_phone": "+234801234567",
        "contact_email": "john@marine.com",
        "is_featured": true,
        "is_verified": true,
        "view_count": 235,
        "created_at": "2025-01-15T10:30:00Z",
        "category": {...},
        "user": {...},
        "equipment_images": [...]
    }
}
```

### 3. Create Equipment Listing
**POST** `/equipment`

Create a new equipment listing.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "title": "Honda 40HP Outboard Motor",
    "description": "Excellent condition marine engine...",
    "category_id": 1,
    "price": 3200000.00,
    "currency": "NGN",
    "condition": "excellent",
    "brand": "Honda",
    "model": "BF40",
    "year": 2020,
    "specifications": {
        "power": "40HP",
        "fuel_type": "Gasoline",
        "weight": "85kg"
    },
    "location_city": "Lagos",
    "location_state": "Lagos",
    "contact_name": "John Marine",
    "contact_phone": "+234801234567",
    "contact_email": "john@marine.com",
    "images": [
        {
            "image_url": "https://example.com/image1.jpg",
            "is_primary": true,
            "alt_text": "Honda engine front view"
        }
    ]
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Equipment listing created successfully",
    "data": {
        "id": 1,
        "title": "Honda 40HP Outboard Motor",
        ...
    }
}
```

### 4. Update Equipment Listing
**PUT** `/equipment/{id}`

Update an existing equipment listing.

**Headers:** `Authorization: Bearer {token}`

**Request Body:** Same as create, with optional fields

### 5. Delete Equipment Listing
**DELETE** `/equipment/{id}`

Delete an equipment listing.

**Headers:** `Authorization: Bearer {token}`

### 6. Toggle Favorite
**POST** `/equipment/{id}/favorite`

Add or remove equipment from user's favorites.

**Headers:** `Authorization: Bearer {token}`

### 7. Mark as Sold
**POST** `/equipment/{id}/mark-sold`

Mark equipment as sold.

**Headers:** `Authorization: Bearer {token}`

### 8. Get Equipment Analytics
**GET** `/equipment/{id}/analytics`

Get analytics data for equipment listing.

**Headers:** `Authorization: Bearer {token}`

---

## Category Endpoints

### 1. Get All Categories
**GET** `/categories`

Get list of all equipment categories.

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Engines",
            "slug": "engines",
            "description": "Marine engines and propulsion systems",
            "icon": "engine-icon.svg",
            "equipment_count": 45,
            "is_active": true
        }
    ]
}
```

### 2. Get Category with Equipment
**GET** `/categories/{id}`

Get category details with equipment listings.

### 3. Create Category (Admin)
**POST** `/categories`

Create a new equipment category.

**Headers:** `Authorization: Bearer {admin_token}`

### 4. Update Category (Admin)
**PUT** `/categories/{id}`

Update category information.

### 5. Delete Category (Admin)
**DELETE** `/categories/{id}`

Delete a category.

---

## User Management Endpoints

### 1. Get User Profile
**GET** `/user/profile`

Get current user's profile information.

**Headers:** `Authorization: Bearer {token}`

### 2. Update User Profile
**PUT** `/user/profile`

Update user profile information.

**Headers:** `Authorization: Bearer {token}`

### 3. Get User Listings
**GET** `/user/listings`

Get current user's equipment listings.

**Headers:** `Authorization: Bearer {token}`

### 4. Get User Favorites
**GET** `/user/favorites`

Get user's favorite equipment listings.

**Headers:** `Authorization: Bearer {token}`

### 5. Get User Subscription
**GET** `/user/subscription`

Get user's current subscription details.

**Headers:** `Authorization: Bearer {token}`

---

## Subscription Endpoints

### 1. Get Subscription Plans
**GET** `/subscriptions/plans`

Get available subscription plans.

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Basic",
            "slug": "basic",
            "price": 0.00,
            "currency": "NGN",
            "duration_days": 30,
            "features": {
                "listings_limit": 5,
                "featured_listings": 0,
                "priority_support": false
            },
            "is_active": true
        },
        {
            "id": 2,
            "name": "Premium",
            "slug": "premium",
            "price": 15000.00,
            "currency": "NGN",
            "duration_days": 30,
            "features": {
                "listings_limit": 50,
                "featured_listings": 5,
                "priority_support": true
            },
            "is_active": true
        }
    ]
}
```

### 2. Subscribe to Plan
**POST** `/subscriptions/subscribe`

Subscribe user to a plan.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "plan_id": 2,
    "payment_method": "paystack",
    "payment_reference": "pay_abc123"
}
```

### 3. Cancel Subscription
**POST** `/subscriptions/cancel`

Cancel current subscription.

**Headers:** `Authorization: Bearer {token}`

### 4. Get Subscription Usage
**GET** `/subscriptions/usage`

Get subscription usage statistics.

**Headers:** `Authorization: Bearer {token}`

---

## Messaging Endpoints

### 1. Get Conversations
**GET** `/messages/conversations`

Get user's message conversations.

**Headers:** `Authorization: Bearer {token}`

### 2. Get Conversation Messages
**GET** `/messages/conversations/{id}`

Get messages in a conversation.

**Headers:** `Authorization: Bearer {token}`

### 3. Send Message
**POST** `/messages/send`

Send a message in a conversation.

**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "equipment_id": 1,
    "recipient_id": 2,
    "message": "Hello, is this item still available?"
}
```

### 4. Mark as Read
**POST** `/messages/conversations/{id}/read`

Mark conversation as read.

**Headers:** `Authorization: Bearer {token}`

---

## Banner Endpoints

### 1. Get Active Banners
**GET** `/banners`

Get active banner advertisements.

**Query Parameters:**
- `position` (string): Banner position (top, middle, bottom, sidebar)
- `limit` (integer): Maximum banners to return

**Success Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "title": "Marine Equipment Sale",
            "description": "Up to 50% off on selected items",
            "media_url": "https://example.com/banner.jpg",
            "link_url": "https://example.com/sale",
            "position": "top",
            "priority": 1,
            "status": "active",
            "start_date": "2025-01-01",
            "end_date": "2025-01-31"
        }
    ]
}
```

### 2. Create Banner (Admin)
**POST** `/banners`

Create a new banner advertisement.

### 3. Update Banner (Admin)
**PUT** `/banners/{id}`

Update banner information.

### 4. Delete Banner (Admin)
**DELETE** `/banners/{id}`

Delete a banner.

---

## Admin Endpoints

### 1. Get Dashboard Stats
**GET** `/admin/dashboard`

Get admin dashboard statistics.

**Headers:** `Authorization: Bearer {admin_token}`

**Success Response (200):**
```json
{
    "success": true,
    "data": {
        "total_users": 1250,
        "total_listings": 3450,
        "total_revenue": 2500000.00,
        "active_subscriptions": 450,
        "recent_registrations": 25,
        "pending_verifications": 12,
        "monthly_revenue": 750000.00,
        "popular_categories": [
            {
                "name": "Engines",
                "count": 890
            }
        ]
    }
}
```

### 2. Get All Users
**GET** `/admin/users`

Get paginated list of all users.

**Headers:** `Authorization: Bearer {admin_token}`

### 3. Update User Status
**PUT** `/admin/users/{id}/status`

Update user account status.

**Headers:** `Authorization: Bearer {admin_token}`

### 4. Get System Settings
**GET** `/admin/settings`

Get system configuration settings.

**Headers:** `Authorization: Bearer {admin_token}`

### 5. Update System Settings
**PUT** `/admin/settings`

Update system settings.

**Headers:** `Authorization: Bearer {admin_token}`

### 6. Get Analytics
**GET** `/admin/analytics`

Get detailed analytics data.

**Headers:** `Authorization: Bearer {admin_token}`

**Query Parameters:**
- `period` (string): Time period (today, week, month, year)
- `metric` (string): Specific metric to fetch

---

## Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 400 | Bad Request | Invalid request parameters |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

## Rate Limiting

API requests are rate limited:
- **Authenticated users**: 1000 requests per hour
- **Unauthenticated users**: 100 requests per hour

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1640995200
```

## Pagination

Paginated endpoints return meta information:
```json
{
    "data": [...],
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 20,
        "to": 20,
        "total": 200
    },
    "links": {
        "first": "http://api.marine.ng/api/equipment?page=1",
        "last": "http://api.marine.ng/api/equipment?page=10",
        "prev": null,
        "next": "http://api.marine.ng/api/equipment?page=2"
    }
}
```

## File Upload

For endpoints that accept file uploads, use multipart/form-data:
```
Content-Type: multipart/form-data
```

Maximum file size: 10MB per file
Allowed file types: jpg, jpeg, png, gif, pdf, doc, docx

---

*Last updated: January 2025*
*API Version: 1.0*