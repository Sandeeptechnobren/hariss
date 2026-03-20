<?php

namespace App\Services\V1\Hariss_Transaction\Web;

use App\Models\Hariss_Transaction\Web\TempReturnH;
use Carbon\Carbon;

class TempReturnService
{
    public function list(int $perPage, array $filters = [], bool $dropdown = false)
    {
        $query = TempReturnH::with(['customer'])->latest();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {

                $q->where('uuid', 'LIKE', "%$search%")
                    ->orWhere('return_code', 'LIKE', "%$search%")
                    ->orWhere('truckname', 'LIKE', "%$search%")
                    ->orWhere('truckno', 'LIKE', "%$search%")
                    ->orWhere('contactno', 'LIKE', "%$search%")
                    ->orWhere('sap_id', 'LIKE', "%$search%")
                    ->orWhere('reason', 'LIKE', "%$search%")
                    ->orWhere('reason_type', 'LIKE', "%$search%")
                    ->orWhere('message', 'LIKE', "%$search%");

                $q->orWhereHas('customer', function ($qc) use ($search) {
                    $qc->where('osa_code', 'LIKE', "%$search%")
                        ->orWhere('name', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE', "%$search%")
                        ->orWhere('town', 'LIKE', "%$search%")
                        ->orWhere('street', 'LIKE', "%$search%")
                        ->orWhere('contact_no', 'LIKE', "%$search%");
                });
            });
        }

        foreach (
            [
                'customer_id',
                'sap_id',
                'truckno',
                'truckname'
            ] as $field
        ) {
            if (!empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

       $fromDate = !empty($filters['from_date'])
        ? Carbon::parse($filters['from_date'])->toDateString()
        : null;

        $toDate = !empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'])->toDateString()
            : null;

        if ($fromDate || $toDate) {

            if ($fromDate && $toDate) {
                $query->whereDate('created_at', '>=', $fromDate)
                    ->whereDate('created_at', '<=', $toDate);
            }
            elseif ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            elseif ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }

        } else {
            $query->whereDate('created_at', Carbon::today());
        }
        $sortBy    = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query->orderBy($sortBy, $sortOrder);
        if ($dropdown) {
            return $query->get()->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'label' => $item->return_code,
                    'value' => $item->id,
                ];
            });
        }

        return $query->paginate($perPage);
    }

    public function viewByUuid(string $uuid)
{
    return TempReturnH::with(['details.item', 'details.uom', 'customer'])
        ->where('uuid', $uuid)
        ->first();
}

}


