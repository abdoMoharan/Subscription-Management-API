<?php

namespace App\Models;

use App\Models\PlanPrice;
use App\Models\Subscription;
use App\trait\CustomFunctionSoftDeleted;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes, CustomFunctionSoftDeleted;
    protected $fillable = [
        'name',
        'description',
        'trial_days',
        'status',
    ];

    //relation
    public function prices()
    {
        return $this->hasMany(PlanPrice::class, 'plan_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function getPrice(string $currency, string $billingCycle): ?PlanPrice
    {
        return $this->prices()
            ->where('currency', $currency)
            ->where('billing_cycle', $billingCycle)
            ->first();
    }

    public function hasTrial(): bool
    {
        return $this->trial_days > 0;
    }

    public function scopeActive($query, $arg)
    {
        return $query->where('status', $arg);
    }
    public function scopeFilter(Builder $builder, array $filters): Builder
    {
        $builder->when(isset($filters['name']), function ($builder) use ($filters) {
            $builder->where('name', 'like', '%' . $filters['name'] . '%');
        });

        $builder->when(isset($filters['trial_days']), function ($builder) use ($filters) {
            $builder->where('trial_days', $filters['trial_days']);
        });
        $builder->when(isset($filters['status']), function ($builder) use ($filters) {
            $statusValue = $filters['status'] == '0' ? 0 : $filters['status'];
            $builder->where('status', $statusValue);
        });
        return $builder;
    }
}
