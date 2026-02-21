<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $invoices = Invoice::with('student')->get();
        
        return response()->json([
            'message' => 'Invoices retrieved successfully',
            'invoices' => $invoices
        ]);
    }

    /**
     * Store a newly created invoice in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'series' => 'required|string|max:10',
            'student_id' => 'required|exists:users,id',
            'value' => 'required|numeric|min:0',
            'currency' => 'required|in:EUR,RON',
            'due_date' => 'required|date',
            'status' => 'nullable|in:draft,issued,paid,overdue,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify the student exists
        $student = Student::find($request->student_id);
        if (!$student) {
            return response()->json([
                'message' => 'Student not found',
            ], 404);
        }

        // Generate next invoice number for the series
        $number = Invoice::generateNextNumber($request->series);

        $invoice = Invoice::create([
            'title' => $request->title,
            'series' => $request->series,
            'number' => $number,
            'student_id' => $request->student_id,
            'value' => $request->value,
            'currency' => $request->currency,
            'due_date' => $request->due_date,
            'status' => $request->status ?? 'draft',
        ]);

        // Load the student relationship
        $invoice->load('student');

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice
        ], 201);
    }

    /**
     * Display the specified invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $invoice = Invoice::with('student')->findOrFail($id);

        return response()->json([
            'message' => 'Invoice retrieved successfully',
            'invoice' => $invoice
        ]);
    }

    /**
     * Update the specified invoice in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|in:EUR,RON',
            'due_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:draft,issued,paid,overdue,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update only allowed fields (series and number cannot be changed)
        $invoice->update($request->only([
            'title',
            'value',
            'currency',
            'due_date',
            'status',
        ]));

        // Load the student relationship
        $invoice->load('student');

        return response()->json([
            'message' => 'Invoice updated successfully',
            'invoice' => $invoice
        ]);
    }

    /**
     * Remove the specified invoice from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json([
            'message' => 'Invoice deleted successfully'
        ]);
    }
    
    /**
     * Get all invoices for a specific student.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentInvoices($id)
    {
        $student = Student::findOrFail($id);
        
        $invoices = Invoice::where('student_id', $student->id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'message' => 'Student invoices retrieved successfully',
            'student_id' => $student->id,
            'invoices' => $invoices
        ]);
    }
    
    /**
     * Update the status of an invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,issued,paid,overdue,cancelled',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $invoice->status = $request->status;
        $invoice->save();
        
        return response()->json([
            'message' => 'Invoice status updated successfully',
            'invoice' => $invoice
        ]);
    }
}
