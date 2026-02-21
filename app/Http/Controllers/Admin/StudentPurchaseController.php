<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StudentPurchaseController extends Controller
{
    /**
     * Get all purchases for a specific student.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentPurchases($id)
    {
        $student = Student::findOrFail($id);
        
        $purchases = $student->purchasedProducts()
            ->withPivot('purchased_at', 'purchase_price')
            ->orderBy('pivot_purchased_at', 'desc')
            ->get();
        
        return response()->json([
            'message' => 'Student purchases retrieved successfully',
            'student_id' => $student->id,
            'purchases' => $purchases
        ]);
    }
    
    /**
     * Record a new purchase for a student.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordPurchase(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'purchased_at' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $product = Product::findOrFail($request->product_id);
        $purchasedAt = $request->purchased_at ? Carbon::parse($request->purchased_at) : Carbon::now();
        
        // Record the purchase with the current price of the product
        $student->purchasedProducts()->attach($product->id, [
            'purchased_at' => $purchasedAt,
            'purchase_price' => $product->price,
        ]);
        
        return response()->json([
            'message' => 'Purchase recorded successfully',
            'purchase' => [
                'student_id' => $student->id,
                'product_id' => $product->id,
                'product_title' => $product->title,
                'purchased_at' => $purchasedAt,
                'purchase_price' => $product->price,
            ]
        ], 201);
    }
    
    /**
     * Remove a purchase record.
     *
     * @param  int  $studentId
     * @param  int  $purchaseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removePurchase($studentId, $productId)
    {
        $student = Student::findOrFail($studentId);
        $product = Product::findOrFail($productId);
        
        // Detach the specific product from the student's purchases
        $student->purchasedProducts()->detach($product->id);
        
        return response()->json([
            'message' => 'Purchase record removed successfully'
        ]);
    }
}
