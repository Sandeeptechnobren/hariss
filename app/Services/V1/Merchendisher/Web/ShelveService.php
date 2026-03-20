<?php

namespace App\Services\V1\Merchendisher\Web;
use App\Models\Shelve;
use App\Models\CompanyCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Rules\CustomerIdsExist;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Exports\ShelvesExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShelveService
{
 public function getDropdownList(array $merchandiserIds = [])
{
    $query = CompanyCustomer::select('id', 'osa_code', 'business_name', 'merchandiser_id')
        ->whereNotNull('merchandiser_id')
        ->orderBy('osa_code');

    if (!empty($merchandiserIds)) {
        $query->where(function ($q) use ($merchandiserIds) {
            foreach ($merchandiserIds as $id) {
                $q->orWhereRaw("CONCAT(',', merchandiser_id, ',') LIKE ?", ["%,$id,%"]);
            }
        });
    }

    return $query->get();
}


    public function create(array $data): Shelve
    {
        if (!empty($data['customer_id']) && empty($data['customer_ids'])) {
            $data['customer_ids'] = [$data['customer_id']];
        } elseif (!empty($data['customer_ids'])) {
            $customerIds = [];

            foreach ((array) $data['customer_ids'] as $id) {
                if (is_string($id)) {
                    $customerIds = array_merge($customerIds, explode(',', $id));
                } else {
                    $customerIds[] = $id;
                }
            }

            $data['customer_ids'] = array_map('intval', $customerIds);
        } else {
            $data['customer_ids'] = [];
        }

        return Shelve::create($data);
            }


  public function updateByUuid(string $uuid, array $data): ?Shelve
    {
        $shelve = Shelve::where('uuid', $uuid)->first();

        if (!$shelve) {
            return null;
        }
        $shelve->update($data);
        return $shelve;
    }

public function getAll()
    {
      return Shelve::latest()->paginate(50);
    }    
    public function deleteByUuid(string $uuid): void
    {
        $shelve = Shelve::where('uuid', trim($uuid))->first();

        if ($shelve) {
            $shelve->delete();
        }
    }
public function getByUuid(string $uuid): ?Shelve
    {
        return Shelve::where('uuid', $uuid)->first();
    }

 public function globalSearch(int $perPage = 10, ?string $searchTerm = null)
{
    $query = Shelve::with([
        'createdUser:id,name,username',
        'updatedUser:id,name,username',
        'deletedUser:id,name,username',
    ]);

    if (!empty($searchTerm)) {
        $like = '%' . strtolower($searchTerm) . '%';

        $query->where(function ($q) use ($like, $searchTerm) {
            $q->orWhereRaw('LOWER(shelf_name) LIKE ?', [$like])
              ->orWhereRaw('CAST(height AS TEXT) ILIKE ?', [$like])
              ->orWhereRaw('CAST(width AS TEXT) ILIKE ?', [$like])
              ->orWhereRaw('CAST(depth AS TEXT) ILIKE ?', [$like])
              ->orWhereRaw('CAST(id AS TEXT) ILIKE ?', [$like]);

            foreach (['createdUser', 'updatedUser', 'deletedUser'] as $relation) {
                $q->orWhereHas($relation, fn($sub) =>
                    $sub->whereRaw('LOWER(name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(username) LIKE ?', [$like])
                );
            }

            $matchingCustomerIds = \App\Models\CompanyCustomer::where(function ($sub) use ($like) {
                $sub->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(business_name) LIKE ?', [$like]);
            })->pluck('id')->toArray();

            foreach ($matchingCustomerIds as $customerId) {
                $q->orWhereRaw('? = ANY(SELECT jsonb_array_elements_text(customer_ids::jsonb))', [(string)$customerId]);
            }

            $matchingMerchIds = \App\Models\Salesman::where(function ($sub) use ($like) {
                $sub->whereRaw('LOWER(osa_code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
            })->pluck('id')->toArray();

            foreach ($matchingMerchIds as $merchId) {
                $q->orWhereRaw('? = ANY(SELECT jsonb_array_elements_text(merchendiser_ids::jsonb))', [(string)$merchId]);
            }

            if (is_numeric($searchTerm)) {
                $q->orWhereRaw('? = ANY(SELECT jsonb_array_elements_text(customer_ids::jsonb))', [$searchTerm]);
                $q->orWhereRaw('? = ANY(SELECT jsonb_array_elements_text(merchendiser_ids::jsonb))', [$searchTerm]);
            }
        });
    }

    return $query->paginate($perPage);
}

  public function exportCustomerDataForMerchandiser()
{
    $userId = Auth::id();
    $customers = DB::table('tbl_company_customer')
        ->where('merchendiser_ids', $userId)
        ->get();

    $lines = [];
    foreach ($customers as $customer) {
        $lines[] = "ID: {$customer->id}";
        $lines[] = "SAP Code: {$customer->sap_code}";
        $lines[] = "Customer Code: {$customer->customer_code}";
        $lines[] = "Business Name: " . ($customer->business_name ?? 'N/A');
        $lines[] = "Customer Type: {$customer->customer_type}";
        $lines[] = "Owner Name: {$customer->owner_name}";
        $lines[] = "Owner No: {$customer->owner_no}";
        $lines[] = "Is WhatsApp: {$customer->is_whatsapp}";
        $lines[] = "WhatsApp No: " . ($customer->whatsapp_no ?? 'N/A');
        $lines[] = "Email: " . ($customer->email ?? 'N/A');
        $lines[] = "Language: {$customer->language}";
        $lines[] = "Contact No 2: " . ($customer->contact_no2 ?? 'N/A');
        $lines[] = "Buyer Type: {$customer->buyerType}";
        $lines[] = "Road/Street: " . ($customer->road_street ?? 'N/A');
        $lines[] = "Town: " . ($customer->town ?? 'N/A');
        $lines[] = "Landmark: " . ($customer->landmark ?? 'N/A');
        $lines[] = "District: " . ($customer->district ?? 'N/A');
        $lines[] = "Balance: {$customer->balance}";
        $lines[] = "Payment Type: {$customer->payment_type}";
        $lines[] = "Bank Name: {$customer->bank_name}";
        $lines[] = "Bank Account Number: {$customer->bank_account_number}";
        $lines[] = "Credit Day: {$customer->creditday}";
        $lines[] = "TIN No: {$customer->tin_no}";
        $lines[] = "Accuracy: " . ($customer->accuracy ?? 'N/A');
        $lines[] = "Credit Limit: {$customer->creditlimit}";
        $lines[] = "Guarantee Name: {$customer->guarantee_name}";
        $lines[] = "Guarantee Amount: {$customer->guarantee_amount}";
        $lines[] = "Guarantee From: {$customer->guarantee_from}";
        $lines[] = "Guarantee To: {$customer->guarantee_to}";
        $lines[] = "Total Credit Limit: {$customer->totalcreditlimit}";
        $lines[] = "Credit Limit Validity: " . ($customer->credit_limit_validity ?? 'N/A');
        $lines[] = "Region ID: " . ($customer->region_id ?? 'N/A');
        $lines[] = "Area ID: " . ($customer->area_id ?? 'N/A');
        $lines[] = "VAT No: {$customer->vat_no}";
        $lines[] = "Longitude: " . ($customer->longitude ?? 'N/A');
        $lines[] = "Latitude: " . ($customer->latitude ?? 'N/A');
        $lines[] = "Threshold Radius: {$customer->threshold_radius}";
        $lines[] = "DChannel ID: {$customer->dchannel_id}";
        $lines[] = "Status: " . ($customer->status == 1 ? 'Active' : 'Inactive');
        $lines[] = "Created User: {$customer->created_user}";
        $lines[] = "Updated User: {$customer->updated_user}";
        $lines[] = "Created At: " . ($customer->created_at ?? 'N/A');
        $lines[] = "Updated At: " . ($customer->updated_at ?? 'N/A');
        $lines[] = "----------------------------------------";
    }

    $textContent = implode(PHP_EOL, $lines);
    $fileName = 'customer_data_' . Str::uuid() . '.txt';
    Storage::disk('public')->put($fileName, $textContent);
    return asset('storage/' . $fileName);
}

public function getShelvesForLoggedInMerchandiser($userId)
{
    $customerIds = CompanyCustomer::where('merchendiser_ids', $userId)
        ->pluck('id');

    if ($customerIds->isEmpty()) {
        return [
            'shelves' => [],
            'file_url' => null,
            'message' => 'No customers found for this merchandiser.',
        ];
    }

    $shelves = Shelve::where(function ($query) use ($customerIds) {
        foreach ($customerIds as $customerId) {
            $query->orWhereJsonContains('customer_ids', $customerId);
        }
    })->get();

    $lines = [];

    foreach ($shelves as $shelf) {
    $lines[] = "Shelf ID: {$shelf->id}";
    $lines[] = "Shelf Name: {$shelf->shelf_name}";
    $lines[] = "Height: {$shelf->height}";
    $lines[] = "Width: {$shelf->width}";
    $lines[] = "Depth: {$shelf->depth}";
    $lines[] = "Valid From: " . ($shelf->valid_from ?? 'N/A');
    $lines[] = "Valid To: " . ($shelf->valid_to ?? 'N/A');

    if (is_string($shelf->customer_ids)) {
        $customerIds = json_decode($shelf->customer_ids, true);
    } elseif (is_array($shelf->customer_ids)) {
        $customerIds = $shelf->customer_ids;
    } else {
        $customerIds = [];
    }

    $lines[] = "Customer IDs: " . (!empty($customerIds) ? implode(', ', $customerIds) : 'N/A');

    $lines[] = "Created User: " . ($shelf->created_user ?? 'N/A');
    $lines[] = "Updated User: " . ($shelf->updated_user ?? 'N/A');
    $lines[] = "Deleted User: " . ($shelf->deleted_user ?? 'N/A');
    $lines[] = "Created At: " . ($shelf->created_at ?? 'N/A');
    $lines[] = "Updated At: " . ($shelf->updated_at ?? 'N/A');
    $lines[] = "Deleted At: " . ($shelf->deleted_at ?? 'N/A');
    $lines[] = str_repeat('-', 40);
}

    $fileContent = implode(PHP_EOL, $lines);
    $fileName = 'shelves_' . $userId . '_' . now()->timestamp . '.txt';
    Storage::disk('public')->put($fileName, $fileContent);
    $fileUrl = asset('storage/' . $fileName);

    return [
        'shelves' => $shelves->toArray(),
        'file_url' => $fileUrl,
        'message' => count($shelves) > 0
            ? 'Shelves fetched successfully.'
            : 'No shelves found for the matched customers.',
    ];
}

public function importFromCsv($file)
{
    $path = $file->getRealPath();

    if ($file->getClientOriginalExtension() === 'csv') {
        $data = array_map('str_getcsv', file($path));
    } else {
        $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);
        $data = $data[0];
    }

    if (empty($data) || count($data) < 2) {
        throw new \Exception("No data found in the uploaded file.");
    }

    $header = array_map('trim', array_shift($data)); 
    $failures = [];

    DB::beginTransaction();

    try {
        $processedRows = array_map(function ($row, $index) use ($header, &$failures) {
            $rowData = array_combine($header, $row);

            // Handle Excel-style date conversions
            foreach (['valid_from', 'valid_to'] as $dateField) {
                if (isset($rowData[$dateField]) && is_numeric($rowData[$dateField])) {
                    $rowData[$dateField] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rowData[$dateField])->format('Y-m-d');
                }
            }

            // Initialize rules
            $customerRule = new CustomerNamesExist();
            $merchandiserRule = new MerchandiserNamesExist();

            // Run validation
            $validator = Validator::make($rowData, [
                'shelf_name'          => 'required|string|max:255',
                'height'              => 'required|numeric|min:0',
                'width'               => 'required|numeric|min:0',
                'depth'               => 'required|numeric|min:0',
                'valid_from'          => 'required|date',
                'valid_to'            => 'required|date|after_or_equal:valid_from',
                'customer_names'      => ['required', 'string', $customerRule],
                'merchandiser_names'  => ['required', 'string', $merchandiserRule],
            ]);

            if ($validator->fails()) {
                $failures[] = [
                    'row_number' => $index + 2,
                    'errors' => $validator->errors()->toArray(),
                    'data' => $rowData,
                ];
                return null;
            }

            // Data transformation
            $rowData['height'] = (float) $rowData['height'];
            $rowData['width'] = (float) $rowData['width'];
            $rowData['depth'] = (float) $rowData['depth'];
            $rowData['valid_from'] = Carbon::parse($rowData['valid_from']);
            $rowData['valid_to'] = Carbon::parse($rowData['valid_to']);

            // Get matched customer and merchandiser IDs
            $rowData['customer_ids'] = array_values($customerRule->getMatchedIds());
            $rowData['merchendiser_ids'] = array_values($merchandiserRule->getMatchedIds());

            // Remove the original name fields
            unset($rowData['customer_names'], $rowData['merchandiser_names']);

            return $rowData;
        }, $data, array_keys($data));

        // Filter valid data
        $validRows = array_filter($processedRows);

        $inserted = array_map(function($rowData) {
            return $this->create($rowData); // Save to DB
        }, $validRows);

        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Import failed', ['exception' => $e]);
        throw $e;
    }

    if (!empty($failures)) {
        Log::warning('Some rows failed to import', ['failures' => $failures]);
    }

    return [
        'inserted_count' => count($inserted),
        'skipped_count' => count($failures),
        'failures' => $failures,
    ];
}       
public function getFilteredShelves($from = null, $to = null)
{
    $query = Shelve::query();

    if ($from) {
        $query->whereDate('valid_from', '>=', $from);
    }

    if ($to) {
        $query->whereDate('valid_to', '<=', $to);
    }

    return $query->latest()->get();
}

}