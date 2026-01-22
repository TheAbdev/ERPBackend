# API Documentation Guide

This document provides guidelines for adding Swagger/OpenAPI documentation to all API endpoints.

## Setup

Swagger is already configured using `darkaonline/l5-swagger`. To generate documentation:

```bash
php artisan l5-swagger:generate
```

Access the documentation at: `/api/documentation`

## Adding Documentation to Controllers

### Basic Structure

Add PHPDoc annotations above each controller method:

```php
/**
 * @OA\Get(
 *     path="/api/crm/leads",
 *     summary="List all leads",
 *     tags={"CRM - Leads"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(...),
 *     @OA\Response(...)
 * )
 */
public function index() { ... }
```

### Common Patterns

#### GET Endpoint (List)

```php
/**
 * @OA\Get(
 *     path="/api/crm/leads",
 *     summary="List all leads",
 *     tags={"CRM - Leads"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="integer", example=15)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Lead"))
 *         )
 *     )
 * )
 */
```

#### POST Endpoint (Create)

```php
/**
 * @OA\Post(
 *     path="/api/crm/leads",
 *     summary="Create a new lead",
 *     tags={"CRM - Leads"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/LeadRequest")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="data", ref="#/components/schemas/Lead"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
```

#### PUT/PATCH Endpoint (Update)

```php
/**
 * @OA\Put(
 *     path="/api/crm/leads/{id}",
 *     summary="Update a lead",
 *     tags={"CRM - Leads"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/LeadRequest")
 *     ),
 *     @OA\Response(response=200, description="Updated successfully")
 * )
 */
```

#### DELETE Endpoint

```php
/**
 * @OA\Delete(
 *     path="/api/crm/leads/{id}",
 *     summary="Delete a lead",
 *     tags={"CRM - Leads"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Deleted successfully")
 * )
 */
```

## Defining Schemas

Create schema definitions in a separate file or at the top of your controller:

```php
/**
 * @OA\Schema(
 *     schema="Lead",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", example="john@example.com"),
 *     @OA\Property(property="status", type="string", example="new"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="LeadRequest",
 *     type="object",
 *     required={"name", "email"},
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="phone", type="string", example="+1234567890"),
 *     @OA\Property(property="status", type="string", example="new")
 * )
 */
```

## Controller Tags

Group related endpoints using tags:

```php
/**
 * @OA\Tag(
 *     name="CRM - Leads",
 *     description="Lead management endpoints"
 * )
 */
class LeadController extends Controller { ... }
```

## Security

All endpoints use Laravel Sanctum authentication:

```php
security={{"sanctum": {}}}
```

## Response Codes

Common response codes:
- `200` - Success
- `201` - Created
- `401` - Unauthenticated
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Controllers to Document

### CRM Module
- [x] LeadController (example completed)
- [ ] ContactController
- [ ] AccountController
- [ ] DealController
- [ ] PipelineController
- [ ] ActivityController
- [ ] NoteController
- [ ] EmailAccountController
- [ ] EmailTemplateController
- [ ] EmailCampaignController
- [ ] ReportsController

### ERP Module
- [ ] ProductController
- [ ] ProductCategoryController
- [ ] InventoryController
- [ ] SalesOrderController
- [ ] SalesInvoiceController
- [ ] PurchaseOrderController
- [ ] PaymentController
- [ ] ExpenseController
- [ ] JournalEntryController
- [ ] ReportController

### Core Module
- [ ] UserController
- [ ] RoleController
- [ ] PermissionController
- [ ] NotificationController
- [ ] AuditLogController

## Generating Documentation

After adding annotations:

```bash
php artisan l5-swagger:generate
```

## Postman Collection

Export Postman collection from Swagger UI or use:

```bash
php artisan l5-swagger:generate
# Then export from /api/documentation
```

## Best Practices

1. **Be Descriptive**: Use clear summaries and descriptions
2. **Include Examples**: Provide example values for all properties
3. **Document Errors**: Include all possible error responses
4. **Use Schemas**: Reuse schema definitions for consistency
5. **Group by Tags**: Use tags to organize endpoints logically
6. **Version Control**: Keep documentation in sync with code changes

## Resources

- [OpenAPI Specification](https://swagger.io/specification/)
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [Swagger UI](https://swagger.io/tools/swagger-ui/)













