<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * List all active products available for purchase.
     */
    public function index()
    {
        $products = Product::where('is_active', true)
            ->with(['singleProduct.test:id,name', 'courseProduct'])
            ->get()
            ->map(fn($p) => $this->format($p));

        return response()->json([
            'message'  => 'Products retrieved successfully',
            'count'    => $products->count(),
            'products' => $products,
        ]);
    }

    /**
     * Show a single active product.
     */
    public function show($id)
    {
        $product = Product::where('is_active', true)
            ->with(['singleProduct.test:id,name', 'courseProduct'])
            ->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'message' => 'Product retrieved successfully',
            'product' => $this->format($product),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function format(Product $product): array
    {
        $data = [
            'id'          => $product->id,
            'type'        => $product->type,
            'name'        => $product->name,
            'description' => $product->description,
            'price_eur'   => $product->price,
            'details'     => null,
        ];

        if ($product->type === 'single' && $product->singleProduct) {
            $sp = $product->singleProduct;
            $data['details'] = [
                'teacher_assistance' => $sp->teacher_assistance,
                'test'               => $sp->test ? [
                    'id'   => $sp->test->id,
                    'name' => $sp->test->name,
                ] : null,
            ];
        } elseif ($product->type === 'course' && $product->courseProduct) {
            $data['details'] = [
                'number_of_courses' => $product->courseProduct->number_of_courses,
            ];
        }

        return $data;
    }
}
