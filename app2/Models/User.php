<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'username',
        'email',
        'password',
        'profile',
        'role',
        'status',
        'region_id',
        'subregion_id',
        'salesman_id',
        'subdepot_id',
        'Modifier_Id',
        'Modifier_Name',
        'Modifier_Date',
        'Login_Date',
        'is_list',
        'created_user',
        'updated_user',
        'Created_Date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'Modifier_Date' => 'datetime',
            'Login_Date' => 'datetime',
            'Created_Date' => 'datetime',
        ];
    }
}
