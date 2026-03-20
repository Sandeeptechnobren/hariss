<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\V1\CodeReservationRequest;
use App\Services\V1\CodeGeneratorService;
use App\Models\DraftReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CodeController extends Controller
{
    public function reserve(CodeReservationRequest $request, CodeGeneratorService $codeGenerator): JsonResponse
    {
        $modelName = $request->input('model_name');
        
        $prefix = config('codes.' . $modelName); 
        $userId = auth()->id();
        
        try {
            $reservedCode = $codeGenerator->generateNextCodeAtomically($prefix);
            
            DraftReservation::create([ 
                'reservation_code' => $reservedCode,
                'record_type' => $prefix, 
                'user_id' => $userId,
                'expires_at' => now()->addMinutes(10) 
                ]);

            return response()->json([
                'success' => true,
                'code' => $reservedCode,
                'prefix' => $prefix,
                'message' => "Code {$reservedCode} reserved successfully for {$modelName}. Expires in 10 minutes."
            ]);
            
        } catch (\Exception $e) {
            Log::error("Code reservation failed for prefix {$prefix}. Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An internal error occurred during code reservation. Please try again.',
            ], 500);
        }
    }
    public function finalize(CodeReservationRequest $request): JsonResponse
    {
        $request->validate([
            'reserved_code' => 'required|string|max:50',
            'model_name' => 'required|string|max:50',
        ]);
        $code = $request->input('reserved_code');
        $modelName = $request->input('model_name');
        $userId = auth()->id();
        try {
            DB::transaction(function () use ($code, $userId) {
                $reservation = DraftReservation::where('reservation_code', $code)
                    ->where('user_id', $userId) 
                    ->firstOrFail(); 
                $reservation->delete();
            });
            return response()->json([
                'success' => true,
                'code' => $code,
                'message' => "Reservation for code {$code} successfully deleted. The final record can proceed.",
            ], 200);

        } catch (ModelNotFoundException $e) {
             return response()->json(['success' => false, 'message' => 'Invalid, expired, or unauthorized code reservation. Cannot delete.'], 403);
        } catch (\Exception $e) {
            Log::error("Code finalization failed for {$code}. Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete reservation due to a server error.'], 500);
        }
    }
}


