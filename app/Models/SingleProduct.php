<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SingleProduct extends Model
{
    protected $fillable = [
        'product_id',
        'teacher_assistance',
        'test_id',
    ];

    protected $casts = [
        'teacher_assistance' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}
