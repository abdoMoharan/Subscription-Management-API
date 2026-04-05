<?php

namespace App\Http\Requests\Base;


use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

abstract class ApiRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     */
    abstract public function authorize();

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    abstract public function rules();

    protected function failedValidation(Validator $validator)
    {

        if ($this->is('api/*')) {
            $response = ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'validation failed', $validator->errors()->all());
            throw new ValidationException($validator, $response);
        }
    }


}
