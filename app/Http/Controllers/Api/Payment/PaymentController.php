<?php

namespace App\Http\Controllers\Api\Payment;



use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\PaymentRequest;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected SubscriptionService $service;
    public function __construct(SubscriptionService $service)
    {
        $this->service = $service;
    }

    public function success(PaymentRequest $request): JsonResponse
    {
        $data = $request->getData();
        $subscription = Subscription::findOrFail($data['subscription_id']);
        if ($subscription->isCanceled()) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, 'Cannot pay for a canceled subscription');
        }
        $subscription = $this->service->handleSuccessfulPayment($subscription);
        $subscription->load('planPrice');
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Payment recorded. Subscription is now active.', SubscriptionResource::make($subscription));
    }

    public function fail(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|exists:subscriptions,id',
            'reason'          => 'nullable|string',
        ]);

        $subscription = Subscription::findOrFail($request->subscription_id);

        if (! $subscription->isActive()) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, 'Subscription is not active.');
        }

        $subscription = $this->service->handleFailedPayment(
            $subscription,
            $request->reason
        );

        return ApiResponse::apiResponse(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, 'Payment failed. Grace period of 3 days started.', SubscriptionResource::make($subscription));
    }
}
