<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;

class SurveyHeader extends Model
{
    
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'survey_headers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'merchandiser_id',
        'date',
        'answerer_name',
        'address',
        'phone',
        'survey_id',
        'uuid',
    ];

    protected $casts = [
        'date' => 'date',
    ];

        public function survey()
    {
         return $this->belongsTo(Survey::class, 'survey_id');   
    }

        public function details()
    {
        return $this->hasMany(SurveyDetail::class, 'header_id', 'id');
    }

}
