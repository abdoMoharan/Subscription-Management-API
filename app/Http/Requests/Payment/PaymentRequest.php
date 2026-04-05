<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\Base\ApiRequest;
class PaymentRequest extends ApiRequest
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
            'subscription_id' => 'required|exists:subscriptions,id',
        ];
    }
    public function getData()
    {
        $data = $this->validated();
        return $data;
    }
}
