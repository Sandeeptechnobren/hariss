<?php

namespace App\Services\V1\Merchendisher\Web;

use App\Models\SurveyQuestion;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Survey;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class SurveyQuestionService
{
public function create(array $data): SurveyQuestion
{
  if (!empty($data['question_based_selected'])) {
    if (is_array($data['question_based_selected'])) {

        $data['question_based_selected'] = implode(',', $data['question_based_selected']);
    } else {
        
        $data['question_based_selected'] = trim($data['question_based_selected'], "\"' \t\n\r\0\x0B");
    }
}
    return SurveyQuestion::create($data);
}
    public function update(SurveyQuestion $question, array $data): SurveyQuestion
    {
        $question->survey_id = $data['survey_id'] ?? $question->survey_id;
        $question->question = $data['question'] ?? $question->question;
        $question->question_type = $data['question_type'] ?? $question->question_type;

        if (isset($data['question_based_selected'])) {
            $question->question_based_selected = is_array($data['question_based_selected'])
                ? $data['question_based_selected']
                : (array) $data['question_based_selected'];
        }

        $question->save();

        return $question;
    }

    public function delete(SurveyQuestion $question): bool
    {
        return $question->delete();
    }

    public function findOrFail(int $id): SurveyQuestion
    {
        $question = SurveyQuestion::with('survey')->find($id);

        if (!$question) {
            throw new ModelNotFoundException("Survey question not found");
        }

        return $question;
    }

        public function index(int $perPage = 10)
    {
        return SurveyQuestion::with('survey')
           ->where('created_user', Auth::id()) 
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'SQ-' . strtoupper(Str::random(6));
        } while (SurveyQuestion::where('survey_question_code', $code)->exists());

        return $code;
    }

      public function globalSearch(?string $searchTerm, int $perPage = 10)
    {
        $query = SurveyQuestion::query()
            ->with([
                'createdUser:id,firstname,lastname,username',
                'updatedUser:id,firstname,lastname,username',
                'deletedUser:id,firstname,lastname,username',
            ]);

        if ($searchTerm) {
            $likeSearch = '%' . strtolower($searchTerm) . '%';

            $query->where(function (Builder $q) use ($likeSearch) {
                $q->orWhereRaw('CAST(survey_questions.id AS TEXT) LIKE ?', [$likeSearch])
                  ->orWhereRaw('LOWER(survey_questions.uuid) LIKE ?', [$likeSearch])
                  ->orWhereRaw('LOWER(survey_questions.survey_question_code) LIKE ?', [$likeSearch])
                  ->orWhereRaw('CAST(survey_questions.survey_id AS TEXT) LIKE ?', [$likeSearch])
                  ->orWhereRaw('LOWER(survey_questions.question) LIKE ?', [$likeSearch])
                  ->orWhereRaw('LOWER(survey_questions.question_type) LIKE ?', [$likeSearch])
                  ->orWhereRaw('LOWER(survey_questions.question_based_selected) LIKE ?', [$likeSearch])
                  ->orWhereRaw('CAST(survey_questions.created_at AS TEXT) LIKE ?', [$likeSearch])
                  ->orWhereRaw('CAST(survey_questions.updated_at AS TEXT) LIKE ?', [$likeSearch])
                  ->orWhereRaw('CAST(survey_questions.deleted_at AS TEXT) LIKE ?', [$likeSearch]);

                $q->orWhereHas('createdUser', function (Builder $sub) use ($likeSearch) {
                    $sub->whereRaw('LOWER(firstname) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(lastname) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch]);
                });

                $q->orWhereHas('updatedUser', function (Builder $sub) use ($likeSearch) {
                    $sub->whereRaw('LOWER(firstname) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(lastname) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch]);
                });

                $q->orWhereHas('deletedUser', function (Builder $sub) use ($likeSearch) {
                    $sub->whereRaw('LOWER(firstname) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(lastname) LIKE ?', [$likeSearch])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$likeSearch]);
                });
            });
        }

        return $query->orderBy('survey_questions.created_at', 'desc')->paginate($perPage);
    }

     public function getQuestionsBySurveyId($survey_id)
    {
        return SurveyQuestion::where('survey_id', $survey_id)
            ->whereNull('deleted_at')
            ->select('id', 'question','survey_question_code','question_based_selected','question',
        'question_type')
            ->get();
    }

   public function importSurveyQuestions($file)
{
    $data = Excel::toCollection(null, $file)->first()->skip(1);

    $results = [
        'success' => [],
        'failed'  => [],
    ];

    $data->map(function ($row, $index) use (&$results) {
        try {
            $surveyId              = $row[0] ?? null;
            $question              = $row[1] ?? null;
            $questionType          = $row[2] ?? null;
            $questionBasedSelected = $row[3] ?? null;
            $questionCode          = $row[4] ?? null;

            // Check if survey exists
            $surveyExists = Survey::where('id', $surveyId)
                ->whereNull('deleted_at')
                ->exists();

            if (!$surveyExists) {
                throw new \Exception("Survey ID {$surveyId} not found or is soft-deleted.");
            }

            // Clean `question_based_selected` if needed
            if (is_array($questionBasedSelected)) {
                $questionBasedSelected = implode(',', $questionBasedSelected);
            }

            // Build data — do NOT set `survey_question_code` if you want it auto-generated
            $questionData = [
                'survey_id'               => $surveyId,
                'question'                => $question,
                'question_type'           => $questionType,
                'question_based_selected' => $questionBasedSelected,
            ];

            // Only set custom code if explicitly provided
            if (!empty($questionCode)) {
                $questionData['survey_question_code'] = $questionCode;
            }

            // Create — model handles UUID and code
            $surveyQuestion = SurveyQuestion::create($questionData);

            $results['success'][] = [
                'row'     => $index + 2, // +2 because header row was skipped
                'message' => 'Inserted successfully',
                'data'    => $surveyQuestion,
            ];
        } catch (\Exception $e) {
            $results['failed'][] = [
                'row'   => $index + 2,
                'error' => $e->getMessage(),
            ];
        }
    });

    return $results;
}
  public function exportSurveyQuestions(string $format)
{
    $questions = SurveyQuestion::orderBy('created_at', 'asc')->get();

    if ($questions->isEmpty()) {
        throw new \Exception('No survey questions found.');
    }

   $data = $questions->map(function ($item) {
    $questionBasedSelected = $item->question_based_selected;

    if (is_array($questionBasedSelected)) {
        $questionBasedSelected = implode(',', $questionBasedSelected);
    } elseif (is_object($questionBasedSelected)) {
        $questionBasedSelected = json_encode($questionBasedSelected);
    } elseif (empty($questionBasedSelected)) {
        $questionBasedSelected = '';
    }

    return [
        'ID'                      => $item->id,
        'Survey ID'               => $item->survey_id,
        'Question Code'           => $item->survey_question_code,
        'Question'                => $item->question,
        'Question Type'           => $item->question_type,
        'Question Based Selected' => $questionBasedSelected, 
        'UUID'                    => $item->uuid,
    ];
});

    $fileName = 'survey_questions_' . now()->format('Y_m_d_H_i_s');

    if ($format === 'csv') {
        $fileName .= '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($data->first()));

            $data->each(function ($row) use ($file) {
                fputcsv($file, $row);
            });

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

  if ($format === 'xlsx') {
    $fileName .= '.xlsx';

    return Excel::download(
        new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function collection() { return $this->data; }
            public function headings(): array { return array_keys($this->data->first()); }
        },
        $fileName
    );
}

    throw new \Exception('Invalid export format.');
}
}