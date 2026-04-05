<?php

namespace App\Http\Controllers\Api\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\SubscriptionRequest;
use App\Repositories\SubscriptionRepository;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public SubscriptionRepository $repository;

    public function __construct(SubscriptionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    public function store(SubscriptionRequest $request)
    {
        return $this->repository->store($request);
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

    public function cancel($id)
    {
        return $this->repository->cancel($id);
    }
}
