<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class SurveyDetail extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'survey_details';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'header_id',
        'question_id',
        'answer',
        'created_user',
        'updated_user',
        'deleted_user',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uuid' => 'string',
    ];

    /**
     * SurveyHeader relationship
     */
    public function header()
    {
        return $this->belongsTo(SurveyHeader::class, 'header_id');
    }

    /**
     * SurveyQuestion relationship
     */
    public function question()
    {
        return $this->belongsTo(SurveyQuestion::class, 'question_id','id');
    }

      public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_user', 'id');
    }

    public function updatedUser()
    {
        return $this->belongsTo(User::class, 'updated_user', 'id');
    }

    public function deletedUser()
    {
        return $this->belongsTo(User::class, 'deleted_user', 'id');
    }

}