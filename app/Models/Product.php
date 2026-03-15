<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function singleProduct()
    {
        return $this->hasOne(SingleProduct::class);
    }

    public function courseProduct()
    {
        return $this->hasOne(CourseProduct::class);
    }

    public function acquisitions()
    {
        return $this->hasMany(ProductAcquisition::class);
    }
}
