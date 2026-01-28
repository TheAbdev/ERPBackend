<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Core\Models\Tenant;
use App\Modules\ERP\Models\Product;
use App\Modules\ERP\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_products(): void
    {
        Product::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/erp/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'sku'],
                ],
            ]);
    }

    /** @test */
    public function it_can_create_a_product(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $productData = [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'description' => 'Test Description',
            'category_id' => $category->id,
            'unit_of_measure_id' => 1,
            'is_tracked' => true,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/erp/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'sku']]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function it_can_show_a_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson("/api/erp/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['id', 'name', 'sku']]);
    }

    /** @test */
    public function it_can_update_a_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/api/erp/products/{$product->id}", [
            'name' => 'Updated Product',
            'sku' => $product->sku,
            'unit_of_measure_id' => $product->unit_of_measure_id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    /** @test */
    public function it_can_delete_a_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/erp/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}

























