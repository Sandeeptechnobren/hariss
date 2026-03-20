<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Blames
{
    public static function bootBlames()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_user = Auth::id();
                $model->updated_user = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_user = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check() && $model->usesSoftDeletes()) {
                $model->deleted_user = Auth::id();
                $model->save();
            }
        });
    }

    protected function usesSoftDeletes()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($this));
    }
}
