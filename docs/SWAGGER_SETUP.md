# Swagger/OpenAPI Documentation Setup

## Installation

After adding `darkaonline/l5-swagger` to composer.json, run:

```bash
composer install
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
php artisan l5-swagger:generate
```

## Configuration

The Swagger configuration file will be created at `config/l5-swagger.php`.

## Usage

1. Add annotations to your controllers using PHPDoc comments
2. Run `php artisan l5-swagger:generate` to regenerate documentation
3. Access Swagger UI at `/api/documentation`

## Example Controller Annotation

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
 *         description="Page number",
 *         required=false,
 *         @OA\Schema(type="integer")
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

## Notes

- All API endpoints should be documented
- Use proper tags to group endpoints by module
- Include request/response schemas
- Document authentication requirements

