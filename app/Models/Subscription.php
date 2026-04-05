<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use App\trait\CustomFunctionSoftDeleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use SoftDeletes, CustomFunctionSoftDeleted;
    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_price_id',
        'status',
        'trial_ends_at',
        'current_period_ends_at',
        'grace_period_ends_at',
        'canceled_at',
    ];

    //relation
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function planPrice()
    {
        return $this->belongsTo(PlanPrice::class, 'plan_price_id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at?->isFuture();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }
    public function isInGracePeriod(): bool
    {
        return $this->isPastDue()
            && $this->grace_period_ends_at?->isFuture();
    }
    public function hasAccess(): bool
    {
        return in_array($this->status, ['trialing', 'active', 'past_due']);
    }
    public function scopeFilter(Builder $builder, array $filters): Builder
    {
        $builder->when(isset($filters['user_id']), function ($builder) use ($filters) {
            $builder->whereHas('user', function ($query) use ($filters) {
                $query->where('id', $filters['user_id']);
            });
        });
        $builder->when(isset($filters['plan_id']), function ($builder) use ($filters) {
            $builder->whereHas('plan', function ($query) use ($filters) {
                $query->where('id', $filters['plan_id']);
            });
        });
        return $builder;
    }
}
