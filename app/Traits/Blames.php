<?php

// namespace App\Traits;

// use Illuminate\Support\Facades\Auth;

// trait Blames
// {
//     public static function bootBlames()
//     {
//         static::creating(function ($model) {
//             if (Auth::check()) {
//                 $model->created_user = Auth::id();
//                 $model->updated_user = Auth::id();
//             }
//         });

//         static::updating(function ($model) {
//             if (Auth::check()) {
//                 $model->updated_user = Auth::id();
//             }
//         });

//         static::deleting(function ($model) {
//             if (Auth::check() && $model->usesSoftDeletes()) {
//                 $model->deleted_user = Auth::id();
//                 $model->save();
//             }
//         });
//     }

//     protected function usesSoftDeletes()
//     {
//         return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($this));
//     }
// }
namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait Blames
{
    public static function bootBlames()
    {
        static::creating(function ($model) {

            if (Auth::check()) {

                $authId = Auth::id();

                $isUser = User::where('id', $authId)->exists();

                if ($isUser) {

                    $model->created_user = $authId;
                    $model->updated_user = $authId;
                } else {

                    // for agent customer / b2c user
                    if (isset($model->flag_user)) {
                        $model->flag_user = $authId;
                    }
                }
            }
        });

        static::updating(function ($model) {

            if (Auth::check()) {

                $authId = Auth::id();

                $isUser = User::where('id', $authId)->exists();

                if ($isUser) {

                    $model->updated_user = $authId;
                } else {

                    // for agent customer / b2c user
                    if (isset($model->flag_user)) {
                        $model->flag_user = $authId;
                    }
                }
            }
        });

        static::deleting(function ($model) {

            if (Auth::check() && $model->usesSoftDeletes()) {

                $authId = Auth::id();

                $isUser = User::where('id', $authId)->exists();

                if ($isUser) {

                    $model->deleted_user = $authId;
                } else {

                    // for agent customer / b2c user
                    if (isset($model->flag_user)) {
                        $model->flag_user = $authId;
                    }
                }

                $model->save();
            }
        });
    }

    protected function usesSoftDeletes()
    {
        return in_array(
            'Illuminate\Database\Eloquent\SoftDeletes',
            class_uses_recursive($this)
        );
    }
}
