<?php

namespace App\Http\Requests\Subscription;

use App\Http\Requests\Base\ApiRequest;

class SubscriptionRequest extends ApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_id'       => 'required|exists:plans,id',
            'currency'      => 'required|in:AED,USD,EGP',
            'billing_cycle' => 'required|in:monthly,yearly',

        ];
    }
    public function getData()
    {
        $data = $this->validated();
        return $data;
    }
}
