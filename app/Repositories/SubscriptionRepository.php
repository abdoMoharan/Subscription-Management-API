<?php

namespace App\Repositories;

use App\Helpers\ApiResponse;
use App\Http\Abstract\BaseRepository;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SubscriptionRepository extends BaseRepository
{
    public Subscription $model;
    private SubscriptionService $service;

    public function __construct(Subscription $model, SubscriptionService $service)
    {
        $this->model = $model;
        $this->service = $service;
    }

    public function index($request)
    {
        $perPage = $request->input('per_page', 10);
        $subscription = $this->model->query()->with(['user', 'plan', 'planPrice', 'payments'])->paginate($perPage);
        if ($subscription->isEmpty()) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'No plans found', []);
        }
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Plans retrieved successfully', SubscriptionResource::collection($subscription));
    }

    public function store($request)
    {
        $data = $request->getData();
        $plan = Plan::findOrFail($data['plan_id']);
        $user = Auth::user();
        try {
            $subscription = $this->service->subscribe($user, $plan, $data['currency'], $data['billing_cycle']);
            $subscription->load(['user', 'plan', 'planPrice']);
            return ApiResponse::apiResponse(JsonResponse::HTTP_CREATED, 'Subscription created successfully', new SubscriptionResource($subscription));
        } catch (\Exception $e) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_BAD_REQUEST, $e->getMessage(), null);
        }
    }

    public function show($id)
    {
        $subscription = $this->model->query()->with(['user', 'plan', 'planPrice', 'payments'])->find($id);
        if (!$subscription) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Subscription not found', null);
        }
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Subscription retrieved successfully', new SubscriptionResource($subscription));
    }

    public function delete($id)
    {
        $subscription = $this->model->query()->find($id);
        if (!$subscription) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Subscription not found', null);
        }
        $subscription->delete();
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Subscription deleted successfully', null);
    }
    public function showDeleted($request)
    {
        $subscriptions = $this->model->onlyTrashed()->with(['user', 'plan', 'planPrice', 'payments'])->get();
        if ($subscriptions->isEmpty()) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'No deleted subscriptions found', []);
        }
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Deleted subscriptions retrieved successfully', SubscriptionResource::collection($subscriptions));
    }
    public function restore($id)
    {
        $subscription = $this->model->onlyTrashed()->find($id);
        if (!$subscription) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Subscription not found', null);
        }
        $subscription->restore();
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Subscription restored successfully', new SubscriptionResource($subscription));
    }
    public function forceDelete($id)
    {
        $subscription = $this->model->onlyTrashed()->find($id);
        if (!$subscription) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Subscription not found', null);
        }
        $subscription->forceDelete();
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Subscription force deleted successfully', null);
    }
    public function cancel($id)
    {
        $subscription = $this->model->find($id);
        if (!$subscription) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Subscription not found', null);
        }
        if ($subscription->isCanceled()) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, 'Subscription already canceled.', null);
        }
        $subscription = $this->service->cancel($subscription);

        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Subscription canceled.', new SubscriptionResource($subscription));
    }
}
