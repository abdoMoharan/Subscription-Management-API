<?php

namespace App\Repositories;

use App\Helpers\ApiResponse;
use App\Http\Abstract\BaseRepository;
use App\Http\Resources\Plan\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;


class PlanRepository extends BaseRepository
{

    public Plan $model;


    public function __construct(Plan $model)
    {
        $this->model = $model;
    }

    public function index($request)
    {
        $perPage = $request->input('per_page', 10);
        $plans = $this->model->active(1)->with('prices')->paginate($perPage);
        if ($plans->isEmpty()) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'No plans found', []);
        }
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Plans retrieved successfully', PlanResource::collection($plans));
    }

    public function store($request)
    {
        $data = $request->getData();
        $plan = $this->model->query()->create($data);
        if (isset($data['plan_prices']) && is_array($data['plan_prices'])) {
            $plan->prices()->createMany($data['plan_prices']);
        }
        $plan->load('prices');
        return ApiResponse::apiResponse(JsonResponse::HTTP_CREATED, 'Plan created successfully', new PlanResource($plan));
    }

    public function update($request, $id)
    {
        $plan = $this->model->query()->find($id);
        if (!$plan) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Plan not found', null);
        }
        $data = $request->getData();
        $plan->update($data);
        if (isset($data['plan_prices']) && is_array($data['plan_prices'])) {
            $plan->prices()->delete();
            $plan->prices()->createMany($data['plan_prices']);
        }
        $plan->load('prices');
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Plan updated successfully', new PlanResource($plan));
    }
    public function show($id)
    {
        $plan = $this->model->query()->with('prices')->find($id);
        if (!$plan) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Plan not found', null);
        }
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Plan retrieved successfully', new PlanResource($plan));
    }

    public function delete($id)
    {
        $plan = $this->model->query()->find($id);
        if (!$plan) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Plan not found', null);
        }
        $plan->delete();
        $plan->prices()->delete();
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Plan deleted successfully', null);
    }
    public function showDeleted($request)
    {
        $plans = $this->model->onlyTrashed()->with('prices')->get();
        if ($plans->isEmpty()) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'No deleted plans found', []);
        }
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Deleted plans retrieved successfully', PlanResource::collection($plans));
    }
    public function restore($id)
    {
        $plan = $this->model->onlyTrashed()->find($id);
        if (!$plan) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Plan not found', null);
        }
        $plan->restore();
        $plan->prices()->restore();
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Plan restored successfully', new PlanResource($plan));
    }
    public function forceDelete($id)
    {
        $plan = $this->model->onlyTrashed()->find($id);
        if (!$plan) {
            return ApiResponse::apiResponse(JsonResponse::HTTP_NOT_FOUND, 'Plan not found', null);
        }
        $plan->forceDelete();
        $plan->prices()->forceDelete();
        return ApiResponse::apiResponse(JsonResponse::HTTP_OK, 'Plan force deleted successfully', null);
    }
}
