<?php

namespace App\trait;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToBranch()
    {
        static::creating(function ($model) {
            if (Auth::check() && ! $model->branch_id) {
                /** @var \App\Models\User|null $user */
                $user = Auth::user();
                // If user is not an owner, assign their branch_id
                if ($user && ! $user->isOwner()) {
                    $model->branch_id = $user->branch_id;
                }
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            // If user is not an owner, filter by their branch_id
            if (! $user->isOwner()) {
                $builder->where($builder->getQuery()->from . '.branch_id', $user->branch_id);
                return;
            }

            $branchId = request()->query('branch_id');
            if (filled($branchId)) {
                $builder->where($builder->getQuery()->from . '.branch_id', $branchId);
            }
        });
    }

    /**
     * Relationship with Branch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
