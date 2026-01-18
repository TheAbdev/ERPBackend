# Frontend Pages & API Reference – CRM & ERP System

## System Overview

This is a SaaS multi-tenant CRM + ERP system built with Laravel 12. Each company operates as a separate tenant with complete data isolation. The system uses API-first architecture with REST endpoints returning JSON responses. Authentication is handled via Laravel Sanctum tokens.

**Key Features:**
- Multi-tenant architecture (automatic tenant resolution)
- Role-based access control (RBAC)
- Integrated CRM and ERP modules
- Workflow and approval engine
- Real-time notifications and activity feed
- Comprehensive audit logging
- Reporting and analytics

---

## 1. Application Roles

### Super Admin (Tenant Owner)
- Full system access
- Manages tenant settings
- Can access all modules and features
- Manages users and permissions

### Admin
- Manages day-to-day operations
- Can access most modules
- Limited system configuration access

### Staff / Employee
- Limited access based on assigned permissions
- Typically view-only or module-specific access
- Cannot modify system settings

---

## 2. Authentication Pages

### Login Page
**Purpose:** User authentication and token generation

**API Endpoints:**
- `POST /api/auth/login`
  - Body: `{ "email": "string", "password": "string", "tenant_id": "integer" }`
  - Returns: `{ "token": "string", "user": {...} }`

**Access:** Public (no authentication required)

---

### Logout Page
**Purpose:** Invalidate user session and token

**API Endpoints:**
- `POST /api/auth/logout`
  - Headers: `Authorization: Bearer {token}`
  - Returns: `{ "message": "Logged out successfully" }`

**Access:** Authenticated users

---

### User Profile (Me)
**Purpose:** Display and manage current user information

**API Endpoints:**
- `GET /api/auth/me`
  - Headers: `Authorization: Bearer {token}`
  - Returns: `{ "data": { "id": 1, "name": "...", "email": "...", "roles": [...], "permissions": [...] } }`

**Access:** Authenticated users

---

## 3. CRM Module Pages

### Leads Management
**Purpose:** Create, view, update, and delete sales leads. Track lead status and conversion.

**API Endpoints:**
- `GET /api/crm/leads` - List all leads (paginated)
- `POST /api/crm/leads` - Create new lead
- `GET /api/crm/leads/{id}` - View lead details
- `PUT /api/crm/leads/{id}` - Update lead
- `PATCH /api/crm/leads/{id}` - Partial update
- `DELETE /api/crm/leads/{id}` - Delete lead

**Access:** Users with `crm.leads.*` permissions

---

### Contacts Management
**Purpose:** Manage customer and prospect contact information.

**API Endpoints:**
- `GET /api/crm/contacts` - List all contacts
- `POST /api/crm/contacts` - Create new contact
- `GET /api/crm/contacts/{id}` - View contact details
- `PUT /api/crm/contacts/{id}` - Update contact
- `PATCH /api/crm/contacts/{id}` - Partial update
- `DELETE /api/crm/contacts/{id}` - Delete contact

**Access:** Users with `crm.contacts.*` permissions

---

### Accounts (Companies)
**Purpose:** Manage company accounts and link contacts to accounts.

**API Endpoints:**
- `GET /api/crm/accounts` - List all accounts
- `POST /api/crm/accounts` - Create new account
- `GET /api/crm/accounts/{id}` - View account details
- `PUT /api/crm/accounts/{id}` - Update account
- `PATCH /api/crm/accounts/{id}` - Partial update
- `DELETE /api/crm/accounts/{id}` - Delete account
- `POST /api/crm/accounts/{id}/contacts/attach` - Attach contacts to account
- `POST /api/crm/accounts/{id}/contacts/detach` - Detach contacts from account

**Access:** Users with `crm.accounts.*` permissions

---

### Deals (Sales Opportunities)
**Purpose:** Manage sales opportunities, track deal stages, and mark deals as won/lost.

**API Endpoints:**
- `GET /api/crm/deals` - List all deals
- `POST /api/crm/deals` - Create new deal
- `GET /api/crm/deals/{id}` - View deal details
- `PUT /api/crm/deals/{id}` - Update deal
- `PATCH /api/crm/deals/{id}` - Partial update
- `DELETE /api/crm/deals/{id}` - Delete deal
- `POST /api/crm/deals/{id}/move-stage` - Move deal to different stage
- `POST /api/crm/deals/{id}/won` - Mark deal as won
- `POST /api/crm/deals/{id}/lost` - Mark deal as lost

**Access:** Users with `crm.deals.*` permissions

---

### Pipelines & Stages
**Purpose:** Configure sales pipelines and manage deal stages.

**API Endpoints:**
- `GET /api/crm/pipelines` - List all pipelines
- `POST /api/crm/pipelines` - Create new pipeline
- `GET /api/crm/pipelines/{id}` - View pipeline details
- `PUT /api/crm/pipelines/{id}` - Update pipeline
- `PATCH /api/crm/pipelines/{id}` - Partial update
- `DELETE /api/crm/pipelines/{id}` - Delete pipeline
- `POST /api/crm/pipelines/{id}/stages` - Create stage in pipeline
- `PUT /api/crm/pipelines/{id}/stages/{stageId}` - Update stage
- `POST /api/crm/pipelines/{id}/stages/reorder` - Reorder stages

**Access:** Users with `crm.pipelines.*` permissions (typically Admin/Super Admin)

---

### Activities & Tasks
**Purpose:** Create and manage activities, tasks, and follow-ups related to CRM entities.

**API Endpoints:**
- `GET /api/crm/activities` - List all activities
- `POST /api/crm/activities` - Create new activity
- `GET /api/crm/activities/{id}` - View activity details
- `PUT /api/crm/activities/{id}` - Update activity
- `PATCH /api/crm/activities/{id}` - Partial update
- `DELETE /api/crm/activities/{id}` - Delete activity
- `POST /api/crm/activities/{id}/complete` - Mark activity as completed

**Access:** Users with `crm.activities.*` permissions

---

### Notes
**Purpose:** Add and manage notes attached to CRM entities (leads, contacts, deals, accounts).

**API Endpoints:**
- `GET /api/crm/notes` - List all notes
- `POST /api/crm/notes` - Create new note
- `GET /api/crm/notes/{id}` - View note details
- `PUT /api/crm/notes/{id}` - Update note
- `PATCH /api/crm/notes/{id}` - Partial update
- `DELETE /api/crm/notes/{id}` - Delete note

**Access:** Users with `crm.notes.*` permissions

---

### CRM Reports
**Purpose:** View analytics and reports for CRM data (leads, deals, activities, sales performance).

**API Endpoints:**
- `GET /api/crm/reports/leads` - Lead analytics report
- `GET /api/crm/reports/deals` - Deal analytics report
- `GET /api/crm/reports/activities` - Activity analytics report
- `GET /api/crm/reports/sales-performance` - Sales performance metrics

**Access:** Users with `crm.reports.*` permissions

---

### CRM Workflows
**Purpose:** Configure and manage CRM workflows for automation and approval processes.

**API Endpoints:**
- `GET /api/crm/workflows` - List all workflows
- `POST /api/crm/workflows` - Create new workflow
- `GET /api/crm/workflows/{id}` - View workflow details
- `PUT /api/crm/workflows/{id}` - Update workflow
- `PATCH /api/crm/workflows/{id}` - Partial update
- `DELETE /api/crm/workflows/{id}` - Delete workflow

**Access:** Users with `crm.workflows.*` permissions (typically Admin/Super Admin)

---

### CRM Import / Export
**Purpose:** Import data from CSV/Excel files and export CRM data.

**Import API Endpoints:**
- `GET /api/crm/imports` - List import history
- `POST /api/crm/imports` - Start new import
- `GET /api/crm/imports/{id}` - View import status/details

**Export API Endpoints:**
- `GET /api/crm/exports` - List export history
- `POST /api/crm/exports` - Start new export
- `GET /api/crm/exports/{id}/download` - Download exported file

**Access:** Users with `crm.imports.*` and `crm.exports.*` permissions

---

## 4. ERP Module Pages

### Products
**Purpose:** Manage product catalog, pricing, and product information.

**API Endpoints:**
- `GET /api/erp/products` - List all products
- `POST /api/erp/products` - Create new product
- `GET /api/erp/products/{id}` - View product details
- `PUT /api/erp/products/{id}` - Update product
- `PATCH /api/erp/products/{id}` - Partial update
- `DELETE /api/erp/products/{id}` - Delete product

**Access:** Users with `erp.products.*` permissions

---

### Product Categories
**Purpose:** Organize products into categories and subcategories.

**API Endpoints:**
- `GET /api/erp/product-categories` - List all categories
- `POST /api/erp/product-categories` - Create new category
- `GET /api/erp/product-categories/{id}` - View category details
- `PUT /api/erp/product-categories/{id}` - Update category
- `PATCH /api/erp/product-categories/{id}` - Partial update
- `DELETE /api/erp/product-categories/{id}` - Delete category

**Access:** Users with `erp.product-categories.*` permissions

---

### Inventory & Stock
**Purpose:** Monitor stock levels, record inventory transactions, and check product availability.

**API Endpoints:**
- `GET /api/erp/inventory/stock-items` - List all stock items
- `GET /api/erp/inventory/stock-items/{id}` - View stock item details
- `POST /api/erp/inventory/transactions` - Record inventory transaction
- `GET /api/erp/inventory/transactions` - List inventory transactions
- `GET /api/erp/inventory/check-availability` - Check product availability
- `GET /api/erp/inventory/low-stock` - Get low stock alerts

**Access:** Users with `erp.inventory.*` permissions

---

### Sales Orders
**Purpose:** Create and manage sales orders, confirm orders, and track deliveries.

**API Endpoints:**
- `GET /api/erp/sales-orders` - List all sales orders
- `POST /api/erp/sales-orders` - Create new sales order
- `GET /api/erp/sales-orders/{id}` - View sales order details
- `PUT /api/erp/sales-orders/{id}` - Update sales order
- `PATCH /api/erp/sales-orders/{id}` - Partial update
- `DELETE /api/erp/sales-orders/{id}` - Delete sales order
- `POST /api/erp/sales-orders/{id}/confirm` - Confirm sales order
- `POST /api/erp/sales-orders/{id}/cancel` - Cancel sales order
- `POST /api/erp/sales-orders/{id}/deliver` - Mark as delivered

**Access:** Users with `erp.sales-orders.*` permissions

---

### Purchase Orders
**Purpose:** Create and manage purchase orders, confirm orders, and track receipts.

**API Endpoints:**
- `GET /api/erp/purchase-orders` - List all purchase orders
- `POST /api/erp/purchase-orders` - Create new purchase order
- `GET /api/erp/purchase-orders/{id}` - View purchase order details
- `PUT /api/erp/purchase-orders/{id}` - Update purchase order
- `PATCH /api/erp/purchase-orders/{id}` - Partial update
- `DELETE /api/erp/purchase-orders/{id}` - Delete purchase order
- `POST /api/erp/purchase-orders/{id}/confirm` - Confirm purchase order
- `POST /api/erp/purchase-orders/{id}/cancel` - Cancel purchase order
- `POST /api/erp/purchase-orders/{id}/receive` - Mark as received

**Access:** Users with `erp.purchase-orders.*` permissions

---

### Accounting Accounts
**Purpose:** Manage chart of accounts for financial reporting.

**API Endpoints:**
- `GET /api/erp/accounts` - List all accounts
- `POST /api/erp/accounts` - Create new account
- `GET /api/erp/accounts/{id}` - View account details
- `PUT /api/erp/accounts/{id}` - Update account
- `PATCH /api/erp/accounts/{id}` - Partial update
- `DELETE /api/erp/accounts/{id}` - Delete account

**Access:** Users with `erp.accounts.*` permissions

---

### Journal Entries
**Purpose:** Create and manage manual journal entries for accounting adjustments.

**API Endpoints:**
- `GET /api/erp/journal-entries` - List all journal entries
- `POST /api/erp/journal-entries` - Create new journal entry
- `GET /api/erp/journal-entries/{id}` - View journal entry details
- `PUT /api/erp/journal-entries/{id}` - Update journal entry
- `PATCH /api/erp/journal-entries/{id}` - Partial update
- `DELETE /api/erp/journal-entries/{id}` - Delete journal entry
- `POST /api/erp/journal-entries/{id}/post` - Post journal entry (requires approval if workflow configured)

**Access:** Users with `erp.journal-entries.*` permissions

---

### ERP Reports
**Purpose:** Generate and export financial, inventory, and operational reports.

**API Endpoints:**
- `GET /api/erp/reports` - List all available reports
- `GET /api/erp/reports/{id}` - View report details and data
- `GET /api/erp/reports/{id}/export?format=csv|excel|pdf` - Export report

**Access:** Users with `erp.reports.*` permissions

**Rate Limit:** 60 requests per minute

---

### ERP Dashboard
**Purpose:** View key metrics, recent activities, and module summaries.

**API Endpoints:**
- `GET /api/erp/dashboard/metrics` - Get dashboard metrics (KPIs)
- `GET /api/erp/dashboard/recent-activities` - Get recent activities
- `GET /api/erp/dashboard/module-summary?module=ERP` - Get module summary

**Access:** Users with `erp.dashboard.*` permissions

**Rate Limit:** 60 requests per minute

---

## 5. System Pages

### Notifications Center
**Purpose:** Display user notifications, mark as read, and view unread count.

**API Endpoints:**
- `GET /api/notifications` - List user notifications
- `POST /api/notifications/{id}/read` - Mark notification as read
- `POST /api/notifications/read-all` - Mark all notifications as read

**ERP Notifications (Enhanced):**
- `GET /api/erp/notifications` - List ERP notifications
- `POST /api/erp/notifications/mark-read` - Mark notification as read
- `POST /api/erp/notifications/mark-all-read` - Mark all as read
- `GET /api/erp/notifications/unread-count` - Get unread count

**Access:** Authenticated users (own notifications)

**Rate Limit (ERP):** 60 requests per minute

---

### Activity Feed
**Purpose:** View system-wide activity feed and entity-specific activity history.

**API Endpoints:**
- `GET /api/erp/activity-feed` - List recent activities
- `GET /api/erp/activity-feed/entity/{entityType}/{entityId}` - Get activities for specific entity

**Access:** Users with `erp.activity-feed.*` permissions

**Rate Limit:** 60 requests per minute

---

### Audit Logs
**Purpose:** View comprehensive audit trail of all system actions and changes.

**API Endpoints:**
- `GET /api/audit-logs` - List audit logs (paginated)
- `GET /api/audit-logs/model-timeline` - Get timeline for specific model
- `GET /api/audit-logs/user/{userId}/timeline` - Get user activity timeline
- `GET /api/audit-logs/recent` - Get recent audit activity

**Access:** Users with `erp.audit.*` permissions

**Rate Limit:** 100 requests per minute

---

### System Health
**Purpose:** Monitor system health, database, cache, queue, and storage status.

**API Endpoints:**
- `GET /api/health` - Public health check (no auth)
- `GET /api/health/database` - Database connection check
- `GET /api/health/cache` - Cache connection check
- `GET /api/health/queue` - Queue connection check
- `GET /api/health/storage` - Storage access check

**ERP System Health:**
- `GET /api/erp/system-health` - Get system health metrics
- `POST /api/erp/system-health/check` - Trigger health check

**Access:** 
- Public health check: No authentication
- Detailed checks: Users with `erp.system-health.*` permissions

**Rate Limit (ERP):** 10 requests per minute

---

### Queue Monitoring
**Purpose:** Monitor queue statistics, failed jobs, and queue metrics.

**API Endpoints:**
- `GET /api/queue/statistics` - Get queue statistics
- `GET /api/queue/failed-jobs` - List failed jobs
- `GET /api/queue/metrics` - Get queue metrics

**Access:** Users with `erp.queue.*` permissions (typically Admin/Super Admin)

**Rate Limit:** 60 requests per minute

---

### System Settings
**Purpose:** Manage tenant-specific system settings and configuration.

**API Endpoints:**
- `GET /api/erp/settings` - List all settings
- `GET /api/erp/settings/{key}` - Get specific setting
- `POST /api/erp/settings` - Create new setting
- `PUT /api/erp/settings/{key}` - Update setting
- `DELETE /api/erp/settings/{key}` - Delete setting

**Access:** Users with `erp.settings.*` permissions (typically Admin/Super Admin)

**Rate Limit:** 30 requests per minute

---

### Webhooks Management
**Purpose:** Configure and manage webhooks for external integrations.

**API Endpoints:**
- `GET /api/erp/webhooks` - List all webhooks
- `POST /api/erp/webhooks` - Create new webhook
- `GET /api/erp/webhooks/{id}` - View webhook details
- `PUT /api/erp/webhooks/{id}` - Update webhook
- `DELETE /api/erp/webhooks/{id}` - Delete webhook

**Access:** Users with `erp.webhooks.*` permissions (typically Admin/Super Admin)

**Rate Limit:** 30 requests per minute

---

## 6. Dashboard Pages

### Main Dashboard (CRM + ERP Metrics)
**Purpose:** Display unified dashboard with key metrics from both CRM and ERP modules.

**API Endpoints:**
- `GET /api/erp/dashboard/metrics` - ERP metrics
- `GET /api/crm/reports/sales-performance` - CRM sales performance
- `GET /api/erp/dashboard/recent-activities` - Recent activities
- `GET /api/erp/dashboard/module-summary?module=ERP` - ERP module summary
- `GET /api/erp/dashboard/module-summary?module=CRM` - CRM module summary (if available)

**Access:** Users with dashboard permissions

---

### Recent Activities
**Purpose:** Show recent system activities across all modules.

**API Endpoints:**
- `GET /api/erp/activity-feed` - Recent activities feed
- `GET /api/audit-logs/recent` - Recent audit logs

**Access:** Users with appropriate permissions

---

### Module Summary
**Purpose:** Display summary statistics for specific modules.

**API Endpoints:**
- `GET /api/erp/dashboard/module-summary?module=ERP` - ERP summary
- `GET /api/erp/dashboard/module-summary?module=CRM` - CRM summary

**Access:** Users with dashboard permissions

---

## 7. API Usage Notes

### Authentication
All protected API endpoints require authentication via Laravel Sanctum. Include the token in the request header:
```
Authorization: Bearer {token}
```

### Tenant Resolution
The tenant is automatically resolved from the request. Include the tenant identifier in the login request or ensure the user's token is associated with the correct tenant. The system handles tenant isolation automatically.

### Role-Based Access Control
All endpoints enforce role-based access control (RBAC). Users must have the appropriate permissions to access endpoints. Permission format: `{module}.{resource}.{action}` (e.g., `crm.leads.view`, `erp.products.create`).

### Error Responses
Errors are returned in JSON format:
```json
{
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

Common HTTP status codes:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests (rate limit exceeded)
- `500` - Server Error

### Rate Limiting
Some endpoints have rate limiting applied (indicated in endpoint descriptions). Rate limits are per tenant and per user. When rate limit is exceeded, the API returns `429 Too Many Requests`.

### Pagination
List endpoints typically return paginated results. Include pagination parameters:
- `page` - Page number
- `per_page` - Items per page

Response format:
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100
  }
}
```

### Request/Response Format
- All requests should use `Content-Type: application/json`
- All responses are JSON
- Use `PUT` for full updates, `PATCH` for partial updates
- Include `X-Tenant-ID` header if tenant cannot be resolved automatically (optional, usually handled by middleware)

