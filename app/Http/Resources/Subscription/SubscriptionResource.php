<?php

namespace App\Http\Resources\Subscription;



use App\Http\Resources\Plan\PlanPriceResource;
use App\Http\Resources\Plan\PlanResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'trial_ends_at' => $this->trial_ends_at,
            'current_period_ends_at' => $this->current_period_ends_at,
            'grace_period_ends_at' => $this->grace_period_ends_at,
            'canceled_at' => $this->canceled_at,
            'user' => UserResource::make($this->whenLoaded('user')),
            'plan' => PlanResource::make($this->whenLoaded('plan')),
            'plan_price' => PlanPriceResource::make($this->whenLoaded('planPrice')),
        ];
    }
}
