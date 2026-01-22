<?php

namespace App\Modules\ECommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Upload image file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $this->authorize('create', \App\Modules\ECommerce\Models\ProductSync::class);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'], // Max 5MB
        ]);

        $file = $request->file('image');
        $tenantId = $request->user()->tenant_id;
        
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store file
        $storedPath = Storage::disk('public')->putFileAs(
            "ecommerce/products/{$tenantId}",
            $file,
            $filename
        );
        
        // Get public URL
        $url = Storage::disk('public')->url($storedPath);

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'data' => [
                'url' => $url,
                'path' => $storedPath,
            ],
        ], 201);
    }

}

