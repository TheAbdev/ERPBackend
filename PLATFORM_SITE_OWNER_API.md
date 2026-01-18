# Site Owner Management API

## Overview

API endpoints for creating and managing Site Owner users. Site Owners have full access to platform-level tenant management.

---

## Endpoints

### 1. Create Site Owner

**POST** `/api/platform/site-owners`

Create a new Site Owner user. This endpoint can be used:
- For initial setup (no authentication required)
- By existing Site Owners to create additional Site Owners

**Request Body:**
```json
{
  "name": "Site Owner Name",
  "email": "siteowner@example.com",
  "password": "securepassword123",
  "password_confirmation": "securepassword123",
  "tenant_id": 1
}
```

**Fields:**
- `name` (required): Site Owner's full name
- `email` (required): Unique email address
- `password` (required): Minimum 8 characters
- `password_confirmation` (required): Must match password
- `tenant_id` (optional): Tenant ID to assign user to (defaults to main tenant)

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Site Owner created successfully.",
  "data": {
    "id": 1,
    "name": "Site Owner Name",
    "email": "siteowner@example.com",
    "tenant": {
      "id": 1,
      "name": "Main Company",
      "slug": "main"
    },
    "role": {
      "id": 1,
      "name": "Site Owner",
      "slug": "site_owner"
    },
    "permissions_count": 115,
    "created_at": "2026-01-15 10:00:00"
  }
}
```

**Notes:**
- If no `tenant_id` is provided, the system will use or create a "main" tenant
- The user is automatically assigned the `site_owner` role
- All permissions (including `platform.manage`) are assigned to the role
- If the tenant doesn't have an owner, this user becomes the tenant owner

---

### 2. List Site Owners

**GET** `/api/platform/site-owners`

List all Site Owner users in the system.

**Authentication:** Required (Site Owner only)

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Site Owner Name",
      "email": "siteowner@example.com",
      "tenant": {
        "id": 1,
        "name": "Main Company",
        "slug": "main"
      },
      "created_at": "2026-01-15 10:00:00"
    }
  ],
  "count": 1
}
```

---

### 3. Assign Platform Permission to User

**POST** `/api/platform/site-owners/assign-permission`

Assign `platform.manage` permission to an existing user (makes them a Site Owner).

**Authentication:** Required (Site Owner only)

**Request Body:**
```json
{
  "user_id": 5
}
```

**Fields:**
- `user_id` (required): ID of the user to grant platform access

**Response:**
```json
{
  "success": true,
  "message": "Platform permission assigned successfully.",
  "data": {
    "user_id": 5,
    "user_name": "John Doe",
    "user_email": "john@example.com",
    "permission": "platform.manage",
    "roles_updated": ["Admin", "Manager"]
  }
}
```

**Notes:**
- The permission is added to all roles the user has
- The user must have at least one role
- This gives the user platform-level access without creating a new `site_owner` role

---

## Usage Examples

### Initial Setup (No Authentication)

```bash
# Create first Site Owner
curl -X POST http://127.0.0.1:8000/api/platform/site-owners \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Platform Administrator",
    "email": "admin@platform.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!"
  }'
```

### Create Additional Site Owner (Authenticated)

```bash
# Login first to get token
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "X-Tenant: main" \
  -d '{
    "email": "admin@platform.com",
    "password": "SecurePass123!"
  }'

# Use token to create another Site Owner
curl -X POST http://127.0.0.1:8000/api/platform/site-owners \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "name": "Second Site Owner",
    "email": "owner2@platform.com",
    "password": "AnotherPass123!",
    "password_confirmation": "AnotherPass123!"
  }'
```

### List All Site Owners

```bash
curl -X GET http://127.0.0.1:8000/api/platform/site-owners \
  -H "Authorization: Bearer {token}"
```

### Grant Platform Access to Existing User

```bash
curl -X POST http://127.0.0.1:8000/api/platform/site-owners/assign-permission \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "user_id": 10
  }'
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Authentication required."
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Only Site Owners can view this list."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Failed to create Site Owner: {error message}"
}
```

---

## Security Considerations

1. **Initial Setup**: The create endpoint allows unauthenticated access for initial setup. Consider:
   - Restricting by IP address in production
   - Adding a setup flag to disable after first Site Owner is created
   - Using environment variables for initial credentials

2. **Password Requirements**: 
   - Minimum 8 characters
   - Consider adding more complex requirements

3. **Token Security**: 
   - Store Site Owner tokens securely
   - Implement token rotation
   - Use HTTPS in production

4. **Audit Logging**: 
   - All Site Owner creation should be logged
   - Track who created which Site Owner

---

## Frontend Integration

### Create Site Owner Form

```javascript
const createSiteOwner = async (formData) => {
  const response = await fetch('/api/platform/site-owners', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      // Include token if authenticated
      ...(token && { 'Authorization': `Bearer ${token}` })
    },
    body: JSON.stringify({
      name: formData.name,
      email: formData.email,
      password: formData.password,
      password_confirmation: formData.passwordConfirmation,
      tenant_id: formData.tenantId || null
    })
  });

  const data = await response.json();
  
  if (data.success) {
    console.log('Site Owner created:', data.data);
  } else {
    console.error('Error:', data.message, data.errors);
  }
};
```

---

## Notes

- Site Owner users have the `site_owner` role with ALL permissions
- Site Owners can access `/api/platform/tenants` endpoints
- The `platform.manage` permission is automatically assigned
- Site Owners belong to a tenant (usually "main") but can manage all tenants
- Multiple Site Owners can exist in the system

