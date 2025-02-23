<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'price',
        'days',
        'is_active',
        'createdby_id',
        'updatedby_id',
        'deletedby_id'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function Customer()
    {
        return $this->belongsToMany(Customer::class);
    }
}
