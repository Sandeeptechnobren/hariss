<?php
namespace App\Http\Controllers\V1\Assets\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Assets\Mob\AgreementRequest;
use App\Services\V1\Assets\Mob\AgreementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Agreement;
use OpenApi\Annotations as OA; 
class AgreementController extends Controller
{
        protected AgreementService $service;

    public function __construct(AgreementService $service)
    {
        $this->service = $service;
    }
/**
 * @OA\Post(
 *     path="/mob/technician_mob/agreement/create",
 *     summary="Create Agreement",
 *     tags={"Agreement"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"ms","ms_of","address","salesman_id","customer_id"},
 *
 *                 @OA\Property(property="osa_code", type="string"),
 *                 @OA\Property(property="ms", type="string"),
 *                 @OA\Property(property="ms_of", type="string"),
 *                 @OA\Property(property="address", type="string"),
 *
 *                 @OA\Property(property="asset_number", type="string"),
 *                 @OA\Property(property="serial_number", type="string"),
 *                 @OA\Property(property="model_branding", type="string"),
 *
 *                 @OA\Property(property="behaf_hariss_name_contact", type="string"),
 *                 @OA\Property(property="behaf_hariss_sign", type="string", format="binary"),
 *                 @OA\Property(property="behaf_reciver_old_signature", type="string", format="binary"),
 *                 @OA\Property(property="behaf_hariss_date", type="string", format="date"),
 *
 *                 @OA\Property(property="behaf_reciver_name_contact", type="string"),
 *                 @OA\Property(property="behaf_reciver_sign", type="string", format="binary"),
 *                 @OA\Property(property="behaf_reciver_date", type="string", format="date"),
 *
 *                 @OA\Property(property="presence_sales_name", type="string"),
 *                 @OA\Property(property="presence_sales_contact", type="string"),
 *                 @OA\Property(property="presence_sign", type="string", format="binary"),
 *
 *                 @OA\Property(property="presence_lc_name", type="string"),
 *                 @OA\Property(property="presence_lc_contact", type="string"),
 *                 @OA\Property(property="presence_lc_sign", type="string", format="binary"),
 *
 *                 @OA\Property(property="presence_landloard_name", type="string"),
 *                 @OA\Property(property="presence_landloard_contact", type="string"),
 *                 @OA\Property(property="presence_landloard_sign", type="string", format="binary"),
 *
 *                 @OA\Property(property="salesman_id", type="integer"),
 *                 @OA\Property(property="customer_id", type="integer"),
 *                 @OA\Property(property="fridge_id", type="integer"),
 *                 @OA\Property(property="ir_id", type="integer"),
 *                 @OA\Property(property="add_chiller_id", type="integer"),
 *
 *                 @OA\Property(property="installed_img1", type="string", format="binary"),
 *                 @OA\Property(property="installed_img2", type="string", format="binary"),
 *                 @OA\Property(property="installed_img3", type="string", format="binary")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Agreement created successfully"
 *     )
 * )
 */
  public function store(AgreementRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $fileFields = [
                'behaf_hariss_sign',
                'behaf_reciver_old_signature',
                'behaf_reciver_sign',
                'presence_sign',
                'presence_lc_sign',
                'presence_landloard_sign',
                'installed_img1',
                'installed_img2',
                'installed_img3', 
            ];
            foreach ($fileFields as $field) {
                $file = $request->file($field);
                if ($file) {
                    $filename = time() . '_compressed_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('agreement', $filename, 'public');
                    $data[$field] = '/storage/' . $path;
                }
            }
            $agreement = $this->service->createAgreement($data);
            $pdfUrl = route('agreement.pdf', ['id' => $agreement->id]);
            return response()->json([
                'status'  => true,
                'message' => 'Agreement created successfully',
                'data'    => $agreement,
                'pdf_url' => $pdfUrl
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

public function downloadPdf($id)
{
    $agreement = Agreement::findOrFail($id);

    // ⭐ Use your blade file name here
    $pdf = Pdf::loadView('agreement', compact('agreement'))
              ->setPaper('a4', 'portrait');

    return $pdf->download('agreement_'.$agreement->id.'.pdf');

    // For opening in browser instead:
    // return $pdf->stream('agreement_'.$agreement->id.'.pdf');
}

}