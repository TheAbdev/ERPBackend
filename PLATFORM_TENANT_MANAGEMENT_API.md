# Platform Tenant Management API Documentation

## Overview

This document describes the Platform-level Tenant Management API endpoints. These endpoints are **ONLY accessible by the Site Owner (Platform Owner)** and are used to manage tenants (companies) in the SaaS system.

**Important:** These routes do NOT use `tenant.resolve` middleware, as they operate at the platform level, not within a tenant context.

---

## Authentication

All endpoints require:
- **Sanctum Authentication**: Valid Bearer token
- **Platform Owner Access**: User must have `site_owner` role OR `platform.manage` permission

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

---

## Base URL

All endpoints are prefixed with `/api/platform/tenants`

---

## Endpoints

### 1. List All Tenants

**GET** `/api/platform/tenants`

List all tenants in the system with pagination and filtering.

**Query Parameters:**
- `status` (optional): Filter by status (`active`, `suspended`, `inactive`)
- `search` (optional): Search by name or slug
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Acme Corporation",
      "slug": "acme-corp",
      "subdomain": "acme",
      "domain": "acme.com",
      "status": "active",
      "owner": {
        "id": 5,
        "name": "John Doe",
        "email": "john@acme.com"
      },
      "usage_stats": {
        "users_count": 25,
        "roles_count": 8,
        "created_at": "2026-01-15 10:00:00",
        "last_activity": "2026-01-15 14:30:00"
      },
      "created_at": "2026-01-15 10:00:00",
      "updated_at": "2026-01-15 14:30:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67
  }
}
```

---

### 2. Create Tenant

**POST** `/api/platform/tenants`

Create a new tenant (company).

**Request Body:**
```json
{
  "name": "New Company Inc",
  "slug": "new-company",           // Optional, auto-generated from name if not provided
  "subdomain": "newcompany",       // Optional
  "domain": "newcompany.com",      // Optional
  "status": "active",               // Optional, default: "active"
  "owner_email": "admin@newcompany.com",  // Optional, assign owner by email
  "owner_user_id": 10,             // Optional, assign owner by user ID
  "settings": {                     // Optional
    "timezone": "UTC",
    "locale": "en"
  }
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Tenant created successfully.",
  "data": {
    "id": 2,
    "name": "New Company Inc",
    "slug": "new-company",
    "subdomain": "newcompany",
    "domain": "newcompany.com",
    "status": "active",
    "owner": {
      "id": 10,
      "name": "Admin User",
      "email": "admin@newcompany.com"
    },
    "created_at": "2026-01-15 15:00:00"
  }
}
```

**Validation Rules:**
- `name`: required, string, max 255
- `slug`: optional, string, max 255, unique
- `subdomain`: optional, string, max 255, unique
- `domain`: optional, string, max 255, unique
- `status`: optional, one of: `active`, `suspended`, `inactive`
- `owner_email`: optional, email, must exist in users table
- `owner_user_id`: optional, integer, must exist in users table
- `settings`: optional, array

**Notes:**
- If `slug` is not provided, it will be auto-generated from `name`
- If both `owner_email` and `owner_user_id` are provided, `owner_user_id` takes precedence
- When owner is assigned, they are automatically:
  - Moved to the tenant (if not already)
  - Assigned the `super_admin` role for that tenant

---

### 3. Get Tenant Details

**GET** `/api/platform/tenants/{tenant}`

Get detailed information about a specific tenant.

**URL Parameters:**
- `tenant`: Tenant ID or slug

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Acme Corporation",
    "slug": "acme-corp",
    "subdomain": "acme",
    "domain": "acme.com",
    "status": "active",
    "owner": {
      "id": 5,
      "name": "John Doe",
      "email": "john@acme.com"
    },
    "usage_stats": {
      "users_count": 25,
      "roles_count": 8,
      "created_at": "2026-01-15 10:00:00",
      "last_activity": "2026-01-15 14:30:00"
    },
    "settings": {
      "timezone": "UTC",
      "locale": "en"
    },
    "created_at": "2026-01-15 10:00:00",
    "updated_at": "2026-01-15 14:30:00"
  }
}
```

---

### 4. Update Tenant

**PUT/PATCH** `/api/platform/tenants/{tenant}`

Update tenant information.

**URL Parameters:**
- `tenant`: Tenant ID or slug

**Request Body:**
```json
{
  "name": "Updated Company Name",
  "slug": "updated-slug",
  "subdomain": "updated",
  "domain": "updated.com",
  "status": "active",
  "settings": {
    "timezone": "America/New_York"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Tenant updated successfully.",
  "data": {
    "id": 1,
    "name": "Updated Company Name",
    "slug": "updated-slug",
    "subdomain": "updated",
    "domain": "updated.com",
    "status": "active",
    "owner": {
      "id": 5,
      "name": "John Doe",
      "email": "john@acme.com"
    },
    "updated_at": "2026-01-15 16:00:00"
  }
}
```

**Validation Rules:**
- All fields are optional (use `sometimes`)
- Same validation as create, but uniqueness checks ignore current tenant

---

### 5. Delete Tenant

**DELETE** `/api/platform/tenants/{tenant}`

Soft delete a tenant (data is preserved).

**URL Parameters:**
- `tenant`: Tenant ID or slug

**Response:**
```json
{
  "success": true,
  "message": "Tenant deleted successfully."
}
```

**Note:** This is a soft delete. The tenant and all related data remain in the database but are marked as deleted.

---

### 6. Assign Owner to Tenant

**POST** `/api/platform/tenants/{tenant}/assign-owner`

Assign or change the owner (Super Admin) of a tenant.

**URL Parameters:**
- `tenant`: Tenant ID or slug

**Request Body:**
```json
{
  "user_id": 10
}
```
OR
```json
{
  "email": "newowner@company.com"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Owner assigned successfully.",
  "data": {
    "id": 1,
    "name": "Acme Corporation",
    "owner": {
      "id": 10,
      "name": "New Owner",
      "email": "newowner@company.com"
    }
  }
}
```

**Validation Rules:**
- Either `user_id` OR `email` is required
- `user_id`: integer, must exist in users table
- `email`: email, must exist in users table

**Notes:**
- If user belongs to a different tenant, they will be moved to this tenant
- User is automatically assigned the `super_admin` role for this tenant

---

### 7. Activate Tenant

**POST** `/api/platform/tenants/{tenant}/activate`

Activate a suspended or inactive tenant.

**URL Parameters:**
- `tenant`: Tenant ID or slug

**Response:**
```json
{
  "success": true,
  "message": "Tenant activated successfully.",
  "data": {
    "id": 1,
    "name": "Acme Corporation",
    "status": "active"
  }
}
```

---

### 8. Suspend Tenant

**POST** `/api/platform/tenants/{tenant}/suspend`

Suspend an active tenant (blocks API access but preserves data).

**URL Parameters:**
- `tenant`: Tenant ID or slug

**Response:**
```json
{
  "success": true,
  "message": "Tenant suspended successfully.",
  "data": {
    "id": 1,
    "name": "Acme Corporation",
    "status": "suspended"
  }
}
```

**Note:** When a tenant is suspended, all API requests from that tenant will be blocked by the `ResolveTenant` middleware.

---

## Error Responses

All endpoints return standardized error responses:

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
  "message": "This action is unauthorized. Only Platform Owner can access this resource."
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Tenant not found."
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "slug": ["The slug has already been taken."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Failed to create tenant: {error message}"
}
```

---

## Frontend Integration Notes

### 1. Authentication
- Store the Site Owner token securely
- Include it in all requests: `Authorization: Bearer {token}`
- Do NOT include `X-Tenant` header for platform routes

### 2. Tenant Status
- `active`: Tenant can access the system normally
- `suspended`: Tenant is blocked from API access (billing issues, etc.)
- `inactive`: Tenant is inactive (not yet activated or deactivated)

### 3. Owner Assignment
- When creating a tenant, you can assign an owner immediately
- Owner must be an existing user in the system
- If user belongs to another tenant, they will be moved

### 4. Usage Statistics
- `users_count`: Total number of users in the tenant
- `roles_count`: Total number of roles in the tenant
- `last_activity`: Last time any user in the tenant was active

### 5. Search and Filtering
- Use query parameters for filtering: `?status=active&search=acme`
- Pagination is handled via `per_page` and `page` parameters

---

## Architecture Decisions

### Why No tenant.resolve Middleware?

Platform routes operate at the **platform level**, not within a tenant context. The Site Owner needs to:
- View all tenants across the system
- Create new tenants (which don't have a context yet)
- Manage tenants regardless of their own tenant_id

### Authorization Strategy

Two methods to identify Site Owner:
1. **Role-based**: User has `site_owner` role
2. **Permission-based**: User has `platform.manage` permission

This provides flexibility for different organizational structures.

### Tenant Isolation

Even though platform routes bypass tenant resolution:
- All tenant-scoped queries still respect `tenant_id`
- Data isolation is maintained at the model level
- Site Owner can only view/manage tenants, not tenant data directly

### Soft Deletes

Tenants use soft deletes to:
- Preserve audit trails
- Allow data recovery
- Maintain referential integrity

---

## Example Usage

### Create Tenant with Owner

```javascript
// Create tenant
const response = await fetch('/api/platform/tenants', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    name: 'New Company',
    subdomain: 'newcompany',
    owner_email: 'admin@newcompany.com',
    status: 'active'
  })
});

const data = await response.json();
console.log('Tenant created:', data.data);
```

### List and Filter Tenants

```javascript
// Get active tenants
const response = await fetch('/api/platform/tenants?status=active&per_page=20', {
  headers: {
    'Authorization': `Bearer ${token}`,
  }
});

const data = await response.json();
console.log('Active tenants:', data.data);
```

### Suspend Tenant

```javascript
// Suspend tenant
const response = await fetch(`/api/platform/tenants/${tenantId}/suspend`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
  }
});

const data = await response.json();
console.log('Tenant suspended:', data.message);
```

---

## Security Considerations

1. **Token Security**: Site Owner tokens should be stored securely and rotated regularly
2. **Rate Limiting**: Consider implementing rate limiting for platform routes
3. **Audit Logging**: All platform actions should be logged for compliance
4. **IP Whitelisting**: Consider restricting platform routes to specific IP addresses
5. **Two-Factor Authentication**: Require 2FA for Site Owner accounts

---

## Support

For issues or questions, contact the development team or refer to the main API documentation.

