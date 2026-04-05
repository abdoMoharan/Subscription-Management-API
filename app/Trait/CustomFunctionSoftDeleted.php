<?php

namespace App\trait;

use Illuminate\Http\Request;

trait CustomFunctionSoftDeleted
{
    public static function getAllDeleted(Request $request)
    {
        return self::onlyTrashed()->get();
    }
    public static function restoreSoft($id)
    {
        $model = self::onlyTrashed()->find($id);
        if ($model) {
            $model->restore();
        }
        return $model;
    }
    public static function forceDeleteById($id)
    {
        $model = self::onlyTrashed()->find($id);
        if ($model) {
            $model->forceDelete();
        }
        return $model;
    }
}
