<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blames;
use Illuminate\Support\Str;

class SurveyQuestion extends Model
{
    use HasFactory, SoftDeletes,Blames;

    protected $table = 'survey_questions';

    protected $fillable = [
        'survey_id',
        'question',
        'question_type',
        'question_based_selected',
        'uuid',
        'survey_question_code', 
    ];

    protected $casts = [
        'question_based_selected' => 'array',
    ];

      protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

              if (empty($model->survey_question_code)) {
                $model->survey_question_code = self::generateUniqueCode();
            }
        });
    }
    
     protected static function generateUniqueCode(): string
    {
        $latestId = self::max('id') ?? 0;
        $nextId = $latestId + 1;

        return 'SURQUE-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    public function survey()
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

        public function details()
    {
        return $this->hasMany(SurveyDetail::class, 'question_id', 'id');
    }
}