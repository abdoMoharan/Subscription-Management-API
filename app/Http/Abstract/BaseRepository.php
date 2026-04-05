<?php

namespace App\Http\Abstract;

abstract class BaseRepository
{
    abstract    public function index($request);
    abstract    public function store($request);
    public function update($request, $id) {}
    abstract    public function delete($id);
    abstract    public function show($id);
    public function showDeleted($request) {}
    public function restore($id) {}
    public function forceDelete($id) {}
}
