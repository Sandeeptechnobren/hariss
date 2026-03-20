<?php

namespace App\Http\Controllers\V1\Assets\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agreement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;


use Barryvdh\DomPDF\Facade\Pdf;
// use PDF;

class AgreementController extends Controller
{
    /**
     * Export Agreement PDF by Agreement ID
     */
    // dd("shjfvhs");
    public function exportAgreementPdfById(Request $request)
    {
        // ✅ Validate input
        $request->validate([
            'id' => 'required|integer|exists:tbl_agreement,id'
        ]);

        $id = $request->input('id');

        try {

            // ✅ Fetch agreement (ignore soft deleted)
            $agreement = Agreement::where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (!$agreement) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Agreement not found.'
                ], 404);
            }
            $agreementDate = $agreement->behaf_hariss_date ?? now();

            $day   = $agreementDate->format('d');
            $month = $agreementDate->format('F'); // October
            $year  = $agreementDate->format('y'); // 24

            // ✅ Optional: Authorization check (if needed)
            // if ($agreement->created_user !== Auth::id()) {
            //     return response()->json([
            //         'status'  => 'error',
            //         'message' => 'Unauthorized access.'
            //     ], 403);
            // }

            // ✅ File naming
            $filename = 'asset_agreement_' . now()->format('Ymd_His') . '.pdf';
            $folder   = 'agreement_exports';
            $path     = $folder . '/' . $filename;
// dd($agreement);
            // ✅ Generate PDF
            $pdf = PDF::loadView('agreement-web', [
                'agreement' => $agreement,
                'day' => $day,
                'month' => $month,
                'year' => $year,
            ])->setPaper('A4', 'portrait');

            // ✅ Ensure directory exists
            Storage::disk('public')->makeDirectory($folder);

            // ✅ Store file
            Storage::disk('public')->put($path, $pdf->output());

            // ✅ Generate public URL
            $downloadUrl = asset('storage/' . $path);

            return response()->json([
                'status'       => 'success',
                'download_url' => $downloadUrl
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
