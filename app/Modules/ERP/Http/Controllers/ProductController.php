<?php

namespace App\Modules\ERP\Http\Controllers;

use App\Events\EntityCreated;
use App\Events\EntityDeleted;
use App\Events\EntityUpdated;
use App\Http\Controllers\Controller;
use App\Modules\ECommerce\Models\ProductSync;
use App\Modules\ECommerce\Models\Store;
use App\Modules\ERP\Http\Requests\StoreProductRequest;
use App\Modules\ERP\Http\Requests\UpdateProductRequest;
use App\Modules\ERP\Http\Resources\ProductResource;
use App\Modules\ERP\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        $query = Product::with(['category'])
            ->where('tenant_id', $request->user()->tenant_id);

        // Filter by category
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by type
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter by status (is_active)
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name, SKU, or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate();

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\StoreProductRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = Product::create($request->validated());
        if($product->type === 'stock') {
            $store=Store::where('tenant_id', $request->user()->tenant_id)->first();
            if($store) {
                $productSync=ProductSync::create([
                    'tenant_id' => $request->user()->tenant_id,
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    'is_synced' => true,
                    'store_visibility' => true,
                   // 'ecommerce_price' => $product->price,
                   // 'ecommerce_images' => $product->images,
                    'ecommerce_description' => $product->description,
                    'sort_order' => 0,
                ]);
            }
        }

        // Dispatch entity created event
        event(new EntityCreated($product, $request->user()->id));

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product->load(['category'])),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\ERP\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return response()->json([
            'data' => new ProductResource($product->load(['category', 'variants', 'stockItems.warehouse'])),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\ERP\Http\Requests\UpdateProductRequest  $request
     * @param  \App\Modules\ERP\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        if($product->type != 'stock' && $request->has('type')) {
            if($request->type === 'stock') {
                $store=Store::where('tenant_id', $request->user()->tenant_id)->first();
                if($store) {
                    $productSync=ProductSync::create([
                        'tenant_id' => $request->user()->tenant_id,
                        'product_id' => $product->id,
                        'store_id' => $store->id,
                        'is_synced' => true,
                        'store_visibility' => true,
                       // 'ecommerce_price' => $product->price,
                       // 'ecommerce_images' => $product->images,
                        'ecommerce_description' => $product->description,
                        'sort_order' => 0,
                    ]);
                }
            }
        }


        $product->update($request->validated());

        // Dispatch entity updated event
        event(new EntityUpdated($product->fresh(), $request->user()->id));

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->load(['category'])),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\ERP\Models\Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        // Dispatch entity deleted event before deletion
        event(new EntityDeleted($product, request()->user()->id));

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}

