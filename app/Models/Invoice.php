<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'series',
        'number',
        'student_id',
        'value',
        'currency',
        'due_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Get the student that the invoice is addressed to.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
    
    /**
     * Generate a new invoice number based on the latest invoice with the same series.
     *
     * @param string $series
     * @return string
     */
    public static function generateNextNumber($series)
    {
        $latestInvoice = self::where('series', $series)
            ->orderBy('number', 'desc')
            ->first();
            
        if ($latestInvoice) {
            // Extract the numeric part and increment
            $numericPart = intval($latestInvoice->number);
            $nextNumber = str_pad($numericPart + 1, 6, '0', STR_PAD_LEFT);
        } else {
            // Start with 000001 if no previous invoice exists
            $nextNumber = '000001';
        }
        
        return $nextNumber;
    }
}
