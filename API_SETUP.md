# Laravel API Setup Guide

## Database Configuration

### 1. Update .env file

Edit `backend/.env` and configure MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=khatabook
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 2. Run Migrations

```bash
cd backend
php artisan migrate
```

This will create:
- `users` table (with mobile_number, shop details, etc.)
- `otps` table (for storing OTPs)
- Other Laravel default tables

## API Endpoints

### POST /api/send-otp
**Request:**
```json
{
  "mobile_number": "9876543210"
}
```

**Response:**
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "data": {
    "mobile_number": "9876543210",
    "otp": "43210",  // Only in development (APP_DEBUG=true)
    "expires_in": 300
  }
}
```

**OTP Generation:** Last 6 digits of mobile number (e.g., 9876543210 → 43210)

### POST /api/verify-otp
**Request:**
```json
{
  "mobile_number": "9876543210",
  "otp": "43210"
}
```

**Response:**
```json
{
  "success": true,
  "message": "OTP verified successfully",
  "data": {
    "user": {
      "id": 1,
      "mobile_number": "9876543210",
      "is_registration_complete": false
    },
    "requires_registration": true
  }
}
```

### POST /api/resend-otp
Same as send-otp endpoint.

## Database Schema

### users table
- `id` - Primary key
- `mobile_number` - Unique, indexed
- `name` - Nullable
- `email` - Nullable, unique
- `shop_name` - Nullable
- `owner_name` - Nullable
- `shop_address` - Nullable (text)
- `business_type` - Nullable
- `password` - Nullable
- `is_registration_complete` - Boolean, default false
- `timestamps`

### otps table
- `id` - Primary key
- `mobile_number` - Indexed
- `otp` - 6 digits
- `is_verified` - Boolean
- `expires_at` - Timestamp
- `timestamps`

## User Flow

1. User enters mobile → `POST /api/send-otp`
2. OTP sent (last 6 digits of mobile)
3. User enters OTP → `POST /api/verify-otp`
4. If user doesn't exist → Create temp user (`is_registration_complete = false`)
5. Navigate to shop setup if registration incomplete
6. After shop setup → Update user and set `is_registration_complete = true`

## Testing

In development mode (APP_DEBUG=true), OTP is returned in API response.
In production, remove OTP from response and send via SMS gateway.

