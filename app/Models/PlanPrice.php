<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanPrice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'plan_id',
        'billing_cycle',
        'currency',
        'price',
    ];
    //relation
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
