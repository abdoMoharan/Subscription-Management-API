<?php

namespace App\Models;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
  protected $fillable = [
    'subscription_id',
    'amount',
    'currency',
    'status',
    'failure_reason',
  ];
//relation
  public function subscription()
  {
    return $this->belongsTo(Subscription::class, 'subscription_id');
  }
}
