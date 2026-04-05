<?php

namespace App\Http\Controllers\Api\Plan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\PlanRequest;
use App\Repositories\PlanRepository;
use Illuminate\Http\Request;

class PlanController extends Controller
{
public PlanRepository $repository;

    public function __construct(PlanRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    public function store(PlanRequest $request)
    {
        return $this->repository->store($request);
    }

    public function update(PlanRequest $request, $id)
    {
        return $this->repository->update($request, $id);
    }

    public function show($id)
    {
        return $this->repository->show($id);
    }

    public function delete($id)
    {
        return $this->repository->delete($id);
    }


    public function showDeleted(Request $request)
    {
        return $this->repository->showDeleted($request);
    }
    public function restore($id)
    {
        return $this->repository->restore($id);
    }
    public function forceDelete($id)
    {
        return $this->repository->forceDelete($id);
    }
}
