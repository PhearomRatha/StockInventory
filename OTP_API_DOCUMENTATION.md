# OTP Authentication API Documentation

## Complete Registration Flow

### 1. Register User
**Endpoint:** `POST /api/auth/register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
}
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Registration successful! Please verify your email with the OTP sent.",
    "step": "verify_otp",
    "data": {
        "email": "john@example.com",
        "name": "John Doe",
        "expires_at": "2026-02-09T02:45:00+07:00",
        "verification_status": "PENDING",
        "otp": "123456" // Only in debug mode
    }
}
```

**Error Responses:**
- 422: Validation failed (check `errors` field)
- 409: Email already registered

---

### 2. Verify OTP
**Endpoint:** `POST /api/auth/verify-otp`

**Request Body:**
```json
{
    "email": "john@example.com",
    "otp": "123456"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Email verified successfully! Your account is now pending admin approval.",
    "step": "awaiting_approval",
    "data": {
        "email": "john@example.com",
        "name": "John Doe",
        "verification_status": "VERIFIED",
        "account_status": "PENDING_APPROVAL",
        "next_step": "wait_for_admin_approval"
    }
}
```

**Error Responses:**
- 400: Invalid OTP, expired OTP, or max attempts reached
- 422: Validation failed

---

### 3. Resend OTP
**Endpoint:** `POST /api/auth/resend-otp`

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "OTP resent successfully! Please check your email.",
    "step": "verify_otp",
    "data": {
        "expires_at": "2026-02-09T02:55:00+07:00",
        "otp": "654321" // Only in debug mode
    }
}
```

---

### 4. Check Registration Status
**Endpoint:** `POST /api/auth/check-status`

**Request Body:**
```json
{
    "email": "john@example.com"
}
```

**Possible Responses:**

**UserRequest Pending (200):**
```json
{
    "success": true,
    "message": "OTP sent. Please verify your email.",
    "step": "verify_otp",
    "can_proceed": true,
    "data": {
        "email": "john@example.com",
        "name": "John Doe",
        "verification_status": "PENDING",
        "is_verified": false
    }
}
```

**UserRequest Verified (200):**
```json
{
    "success": true,
    "message": "Your account is verified. Waiting for admin approval.",
    "step": "awaiting_approval",
    "can_proceed": false,
    "data": {
        "email": "john@example.com",
        "name": "John Doe",
        "verification_status": "VERIFIED",
        "is_verified": true
    }
}
```

**User Exists and Active (200):**
```json
{
    "success": true,
    "message": "Your account is active. You can log in.",
    "step": "can_login",
    "can_login": true,
    "data": {
        "email": "john@example.com",
        "name": "John Doe",
        "account_status": "ACTIVE",
        "is_active": true,
        "role": "User"
    }
}
```

**Not Found (404):**
```json
{
    "success": false,
    "message": "No registration request found for this email.",
    "step": "not_registered",
    "can_proceed": true
}
```

---

### 5. Login (after admin approval)
**Endpoint:** `POST /api/auth/login`

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
    "message": "Login successful! Welcome back.",
    "step": "logged_in",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "User",
            "status": "ACTIVE"
        },
        "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ",
        "token_type": "Bearer",
        "expires_at": "2026-02-10T02:45:00+07:00"
    }
}
```

---

## Frontend Flow Diagram

```
┌─────────────────┐
│   Start         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌─────────────────┐
│ Register        │     │ Login           │
│ POST /register  │     │ POST /login     │
└────────┬────────┘     └─────────────────┘
         │                    ▲
         ▼                    │
┌─────────────────┐           │
│ Check Status    │           │
│ POST /check-status           │
└────────┬────────┘           │
         │                    │
    ┌────┴────┐               │
    │         │               │
    ▼         ▼               │
┌────────┐  ┌────────┐       │
│ Pending│  │Verified│       │
└───┬────┘  └───┬────┘       │
    │          │             │
    ▼          ▼             │
┌────────┐  ┌────────┐       │
│ Verify │  │ Wait   │       │
│ OTP    │  │ Admin  │───────┘
└───┬────┘  └────────┘
    │
    ▼
┌────────┐
│ Login  │
└────────┘
```

## OTP Features
- **OTP Length:** 6 digits
- **Expiration:** 10 minutes
- **Max Attempts:** 5 attempts before OTP expires
- **Auto-resend:** Available via resend endpoint

## Admin Actions (requires Admin role)

### Get Pending Requests
**Endpoint:** `GET /api/admin/pending-requests`

### Approve User
**Endpoint:** `POST /api/admin/approve-user`

**Request Body:**
```json
{
    "user_request_id": 1
}
```

### Reject User
**Endpoint:** `POST /api/admin/reject-user`

**Request Body:**
```json
{
    "user_request_id": 1,
    "reason": "Optional rejection reason"
}
```
