<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'email',
        'phone_no',
        'subdomain',
        'plan_id',
        'register_date',
        'expiry_date',
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

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}
