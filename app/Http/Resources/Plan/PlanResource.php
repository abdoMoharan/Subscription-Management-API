<?php

namespace App\Http\Resources\Plan;

use App\Http\Resources\Plan\PlanPriceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'trial_days' => $this->trial_days,
            'status' => $this->status,
            'prices' => PlanPriceResource::collection($this->whenLoaded('prices')),
        ];
    }
}
