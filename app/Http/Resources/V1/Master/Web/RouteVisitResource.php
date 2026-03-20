<?php

namespace App\Http\Resources\V1\Master\Web;

use App\Models\AgentCustomer;
use App\Models\CompanyCustomer;
use App\Models\Area;
use App\Models\Company;
use App\Models\Region;
use App\Models\Route;
use App\Models\Warehouse;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteVisitResource extends JsonResource
{
    public function toArray($request): array
    {
        // Helper to convert array of IDs to model data
        $mapIdsToModels = function ($ids, $modelClass, $codeField, $nameField) {
            if (empty($ids)) return [];
            return $modelClass::whereIn('id', $ids)->get()->map(function ($item) use ($codeField, $nameField) {
                return [
                    'id' => $item->id,
                    'code' => $item->$codeField,
                    'name' => $item->$nameField,
                ];
            });
        };
        $customer = null;

        if ((int)$this->customer_type === 1 && $this->agentCustomer) {
            $customer = AgentCustomer::find($this->customer_id);
            $customer = [
                'id' => $this->agentCustomer->id,
                'agent_name' => $this->agentCustomer->name,
            ];
        } elseif ((int)$this->customer_type === 2 && $this->companyCustomer) {
            $customer = CompanyCustomer::find($this->customer_id);
            $customer = [
                'id' => $this->companyCustomer->id,
                'company_name' => $this->companyCustomer->business_name,
            ];
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'osa_code' => $this->osa_code,
            'header' => $this->header ? [
                'id'       => $this->header->id,
                'uuid'     => $this->header->uuid,
                'osa_code' => $this->header->osa_code,
            ] : null,
            'customer_type' => $this->customer_type,
            'customer' => $customer,
            'merchandiser' => $this->merchandiser ? [
                'id' => $this->merchandiser->id,
                'code' => $this->merchandiser->osa_code,
                'name' => $this->merchandiser->name,
            ] : null,

            'companies' => $mapIdsToModels($this->company_ids, Company::class, 'company_code', 'company_name'),
            'region' => $mapIdsToModels($this->region_ids, Region::class, 'region_code', 'region_name'),
            'area' => $mapIdsToModels($this->area_ids, Area::class, 'area_code', 'area_name'),
            'warehouse' => $mapIdsToModels($this->warehouse_ids, Warehouse::class, 'warehouse_code', 'warehouse_name'),
            'route' => $mapIdsToModels($this->route_ids, Route::class, 'route_code', 'route_name'),
            'days' => $this->days_list,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'status' => $this->status

        ];
    }
}
