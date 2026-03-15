<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseProduct;
use App\Models\Product;
use App\Models\SingleProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * List all products (including inactive).
     */
    public function index()
    {
        $products = Product::withTrashed()
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
     * Show a single product.
     */
    public function show($id)
    {
        $product = Product::withTrashed()
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

    /**
     * Create a new product.
     */
    public function store(Request $request)
    {
        $base = $request->validate([
            'type'        => 'required|in:single,course',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'is_active'   => 'boolean',
        ]);

        if ($request->type === 'single') {
            $details = $request->validate([
                'teacher_assistance' => 'boolean',
                'test_id'            => 'nullable|integer|exists:tests,id',
            ]);
        } else {
            $details = $request->validate([
                'number_of_courses' => 'required|integer|min:1',
            ]);
        }

        $product = DB::transaction(function () use ($base, $details, $request) {
            $product = Product::create($base);

            if ($request->type === 'single') {
                SingleProduct::create(array_merge(
                    ['product_id' => $product->id],
                    $details
                ));
            } else {
                CourseProduct::create(array_merge(
                    ['product_id' => $product->id],
                    $details
                ));
            }

            return $product->load(['singleProduct.test', 'courseProduct']);
        });

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $this->format($product),
        ], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $base = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'is_active'   => 'boolean',
        ]);

        if ($product->type === 'single') {
            $details = $request->validate([
                'teacher_assistance' => 'boolean',
                'test_id'            => 'nullable|integer|exists:tests,id',
            ]);
        } else {
            $details = $request->validate([
                'number_of_courses' => 'sometimes|integer|min:1',
            ]);
        }

        DB::transaction(function () use ($product, $base, $details) {
            $product->update($base);

            if ($product->type === 'single') {
                $product->singleProduct()->updateOrCreate(
                    ['product_id' => $product->id],
                    $details
                );
            } else {
                $product->courseProduct()->updateOrCreate(
                    ['product_id' => $product->id],
                    $details
                );
            }
        });

        $product->load(['singleProduct.test', 'courseProduct']);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $this->format($product),
        ]);
    }

    /**
     * Soft-delete a product.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
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
            'is_active'   => $product->is_active,
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
