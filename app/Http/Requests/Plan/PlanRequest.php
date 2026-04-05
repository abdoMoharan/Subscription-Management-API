<?php

namespace App\Http\Requests\Plan;

use App\Http\Requests\Base\ApiRequest;

class PlanRequest extends ApiRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trial_days' => 'required|integer|min:0',
            'status' => 'nullable|in:0,1',
            'plan_prices' => 'required|array',
            'plan_prices.*.billing_cycle' => 'required|in:monthly,yearly',
            'plan_prices.*.currency' => 'required|in:AED,USD,EGP',
            'plan_prices.*.price' => 'required|numeric|min:0',
        ];
    }


    public function getData()
    {
        $data = $this->validated();
        return $data;
    }
}
