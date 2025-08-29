# Marine.ng API Quick Start Guide

## Base URL
```
http://localhost:8000/api/v1
```

## Test Credentials

### Admin User
```
Email: admin@marineng.com
Password: password123
```

### Moderator User
```
Email: moderator@marineng.com
Password: password123
```

### Regular Users (Sellers)
```
Email: seller1@marineng.com
Password: password123

Email: seller2@marineng.com
Password: password123
```

### Regular Users (Buyers)
```
Email: buyer1@marineng.com
Password: password123

Email: buyer2@marineng.com
Password: password123
```

## Common API Endpoints

### 1. Login
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@marineng.com","password":"password123"}'
```

### 2. Register
```bash
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "New User",
    "email": "newuser@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "company_name": "My Company",
    "phone": "+234801234567",
    "city": "Lagos",
    "state": "Lagos",
    "country": "Nigeria"
  }'
```

### 3. Get Equipment Listings
```bash
curl -X GET http://localhost:8000/api/v1/equipment \
  -H "Accept: application/json"
```

### 4. Get Categories
```bash
curl -X GET http://localhost:8000/api/v1/categories \
  -H "Accept: application/json"
```

### 5. Get User Profile (Authenticated)
```bash
# First login to get token, then use:
curl -X GET http://localhost:8000/api/v1/user/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 6. Create Equipment Listing (Authenticated)
```bash
curl -X POST http://localhost:8000/api/v1/equipment \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "title": "Honda 40HP Outboard Motor",
    "description": "Excellent condition marine engine",
    "category_id": 1,
    "price": 3200000,
    "currency": "NGN",
    "condition": "excellent",
    "brand": "Honda",
    "model": "BF40",
    "year": 2020,
    "location_city": "Lagos",
    "location_state": "Lagos",
    "contact_name": "John Marine",
    "contact_phone": "+234801234567",
    "contact_email": "john@marine.com"
  }'
```

### 7. Search Equipment
```bash
curl -X GET "http://localhost:8000/api/v1/equipment?search=honda&condition=new&price_max=5000000" \
  -H "Accept: application/json"
```

### 8. Logout (Authenticated)
```bash
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Postman/Insomnia Setup

### Environment Variables
```
BASE_URL: http://localhost:8000/api/v1
TOKEN: (set after login)
```

### Headers for Authenticated Requests
```
Authorization: Bearer {{TOKEN}}
Accept: application/json
Content-Type: application/json
```

## Testing the API

1. **Start Laravel Development Server**
   ```bash
   cd marine-backend
   php artisan serve --port=8000
   ```

2. **Test Database Connection**
   ```bash
   php artisan migrate:status
   ```

3. **Seed Test Data**
   ```bash
   php artisan db:seed
   ```

4. **Clear Cache (if needed)**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

## Common Response Codes

- `200 OK` - Request successful
- `201 Created` - Resource created successfully
- `400 Bad Request` - Invalid request data
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Access denied
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation errors
- `500 Internal Server Error` - Server error

## Troubleshooting

### Issue: 404 Not Found
- Make sure you're using `/api/v1/` prefix
- Check if Laravel server is running on port 8000
- Verify route exists: `php artisan route:list`

### Issue: 401 Unauthorized
- Check if token is valid and not expired
- Ensure "Bearer " prefix is included in Authorization header
- Verify user account is active

### Issue: 422 Validation Error
- Check required fields in request body
- Verify data types and formats
- Review validation rules in form requests

### Issue: CORS Errors
- Frontend is configured to use http://localhost:8000
- Laravel CORS is configured in `config/cors.php`
- Allowed origins include localhost:3000 for development

## Support

For API documentation, see: `API_DOCUMENTATION.md`
For issues, check Laravel logs: `storage/logs/laravel.log`