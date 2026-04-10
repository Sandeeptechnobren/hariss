<?php
namespace App\Services\V1\Assets\Mob;

use App\Models\Agreement;
use App\Models\AgentCustomer;
use App\Models\AcFridgeStatus;
use App\Models\AddChiller;
use App\Models\IRHeader;
use App\Models\IRDetail;
use App\Models\IROHeader;
use App\Models\IRODetail;
use App\Models\ChillerRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgreementService
{
  public function createAgreement(array $data)
    {
        DB::beginTransaction();
        try {
            $agreement = Agreement::create($data);
            $agreementId = $agreement->id;
            $customerId  = $data['customer_id'];
            $fridgeId    = $data['fridge_id'] ?? null;
            $irId        = $data['ir_id'] ?? null;
            $addChillerId = $data['add_chiller_id'] ?? null;
            AgentCustomer::where('id', $customerId)
                ->update(['fridge' => 1]);
           if ($fridgeId) {
                $existing = AcFridgeStatus::where('fridge_id', $fridgeId)
                    ->whereNull('remove_date')
                    ->first();
                if ($existing) {
                    $existing->update([
                        'remove_date' => Carbon::today()
                    ]);
                }
                AcFridgeStatus::create([
                    'customer_id'  => $customerId,
                    'fridge_id'    => $fridgeId,
                    'install_date' => Carbon::today(),
                    'remove_date'  => null,
                    'agrement_id'  => $agreementId,
                ]);
            }
            if ($fridgeId) {
                AddChiller::where('id', $fridgeId)
                    ->update([
                        'agreement_id' => $agreementId,
                        'is_assign'    => 1,
                        'status'       => 1,
                        'document_id'  => $addChillerId,
                        'document_type'=> 'CRF',
                        'customer_id'  => $customerId,
                    ]);
            }
            $irHeader = null;
           if ($irId) {
                $irHeader = IRHeader::find($irId);
                    if ($irHeader) {
                     $irHeader->update(['status' => 5]);
                    }}
            if ($irId) {
                IRDetail::where('header_id', $irId)
                    ->update(['agreement_id' => $agreementId,
                              'status' => 1
                              ]);
            }
            if($irHeader){
                $iroId = $irHeader->iro_id;
                if($iroId){
                    IROHeader::where('id', $iroId)
                    ->update(['status' => 6]);
                }
                if($iroId){
                    IRODetail::where('header_id', $iroId)
                    ->update(['installation_status' => 1
                              ]);
                }
            }
            if($addChillerId){
                $chillerrequest = ChillerRequest::find($addChillerId);
                    if ($chillerrequest) {
                     $chillerrequest->update(['status' => 4]);
                    }}
            DB::commit();
            return $agreement;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}