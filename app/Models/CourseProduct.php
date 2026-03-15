<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseProduct extends Model
{
    protected $fillable = [
        'product_id',
        'number_of_courses',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
