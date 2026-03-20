<?php
namespace App\Services\V1\Merchendisher\Web;

use App\Models\Planogram;
use App\Models\PlanogramImage;
use App\Models\Salesman;
use App\Models\SalesmanType;
use App\Models\Shelve;
use App\Models\CompanyCustomer;
use App\Http\Requests\V1\Merchendisher\Web\PlanogramRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use App\Helpers\SearchHelper;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlanogramService
{
    public function getAll()
    {
        $search = request()->input('search');
        $query = Planogram::latest();
        $query = SearchHelper::applySearch($query, $search, [
            'id',
            'name',
            'valid_from',
            'valid_to',
            
        ]);

        return $query->paginate(request()->get('per_page', 10));
    }

    public function getByuuid($uuid): ?Planogram
    {
        return Planogram::where('uuid', $uuid)->first();
    }

//     public function create(array $data): Planogram
// {
//     $data['uuid'] = (string) Str::uuid();
//     if (empty($data['code'])) {
//         $data['code'] = $this->generateComplaintCode();
//     }
//     return Planogram::create($data);
// }

  public function store(array $data): Planogram
        {
            return DB::transaction(function () use ($data) {
            $planogram = Planogram::create([
            'name' => $data['name'],
            'valid_from' => $data['valid_from'] ?? null,
            'valid_to' => $data['valid_to'] ?? null,
            'merchendisher_id' => $data['merchendisher_id'],
            'customer_id' => $data['customer_id'],
            'shelf_id' =>  $data['shelf_id'], 
        ]);
           
        $imageInputs = request()->input('images', []);
        $imageFiles  = request()->file('images', []);

        foreach ($imageInputs as $merchId => $customers) {
            foreach ($customers as $custId => $shelves) {
                foreach ($shelves as $index => $shelfData) {
                    $shelfId = $shelfData['shelf_id'] ?? null;

                    if (!$shelfId) {
                        throw new \Exception("Missing shelf_id for image at images[{$merchId}][{$custId}][{$index}]");
                    }
                    $imageFile = $imageFiles[$merchId][$custId][$index]['image'] ?? null;
                    $imagePath = null;

                    if ($imageFile) {
                        $path = $imageFile->store('planogram_images', 'public');
                        $imagePath = Storage::url($path);
                    }

                    PlanogramImage::create([
                        'planogram_id'     => $planogram->id,
                        'merchandiser_id'  => (int) $merchId,
                        'customer_id'      => (int) $custId,
                        'shelf_id'         => $shelfId,
                        'image'            => $imagePath,
                    ]);
                }
            }
        }

        return $planogram;
    });
    }

//     protected function generateComplaintCode(): string
// {
//     do {
//         $randomNumber = random_int(1, 999);
//         $code = 'PLAN' . str_pad($randomNumber, 3, '0', STR_PAD_LEFT);
//     } while (Planogram::where('code', $code)->exists());
//     return $code;
// }

   public function update(Planogram $planogram, array $data): Planogram
{
    return DB::transaction(function () use ($planogram, $data) {
        $planogram->update([
            'name' => $data['name'],
            'valid_from' => $data['valid_from'] ?? null,
            'valid_to' => $data['valid_to'] ?? null,
            'merchendisher_id' => $data['merchendisher_id'],
            'customer_id' => $data['customer_id'],
            'shelf_id' => $data['shelf_id'],
        ]);

        // Optional: Delete existing images if replacing
        

        $imageInputs = request()->input('images', []);
        $imageFiles = request()->file('images', []);

        foreach ($imageInputs as $merchId => $customers) {
            foreach ($customers as $custId => $shelves) {
                foreach ($shelves as $index => $shelfData) {
                    $shelfId = $shelfData['shelf_id'] ?? null;

                    if (!$shelfId) {
                        throw new \Exception("Missing shelf_id for image at images[{$merchId}][{$custId}][{$index}]");
                    }

                    $imageFile = $imageFiles[$merchId][$custId][$index]['image'] ?? null;
                    $imagePath = null;

                    if ($imageFile) {
                        $path = $imageFile->store('planogram_images', 'public');
                        $imagePath = Storage::url($path);
                    }

                    PlanogramImage::updateOrCreate(
                        [
                            'planogram_id' => $planogram->id,
                            'merchandiser_id' => (int) $merchId,
                            'customer_id' => (int) $custId,
                            'shelf_id' => $shelfId,
                        ],
                        [
                            'image' => $imagePath,
                        ]
                    );
                }
            }
        }

        return $planogram;
    });
}

     public function delete(Planogram $planogram): bool
    {
        $planogram->save();

        return $planogram->delete();
    }

  public function bulkUpload(Collection $rows)
{
    $header = $rows->shift(); // first row = headers
    $errors = [];

    $rows->map(function ($row, $index) use ($header, &$errors) {
        $data = $header->combine($row)->toArray();

        if (!empty($data['merchendisher'])) {
            $merch = Salesman::where('name', $data['merchendisher'])->first();
            if ($merch) {
                $data['merchendisher_id'] = $merch->id;
            } else {
                $errors[] = [
                    'row' => $index + 2,
                    'errors' => ['merchendisher' => ["Merchendisher '{$data['merchendisher']}' not found."]]
                ];
                return;
            }
        }

        if (!empty($data['customer'])) {
            $cust = CompanyCustomer::where('business_name', $data['customer'])->first();
            if ($cust) {
                $data['customer_id'] = $cust->id;
            } else {
                $errors[] = [
                    'row' => $index + 2,
                    'errors' => ['customer' => ["Customer '{$data['customer']}' not found."]]
                ];
                return;
            }
        }
        $validator = Validator::make($data, (new PlanogramRequest())->rules());
        if ($validator->fails()) {
            $errors[] = [
                'row' => $index + 2,
                'errors' => $validator->errors()->toArray()
            ];
            return;
        }
        $this->create($validator->validated());
    });

    return $errors;
}
     public function getFiltered($validFrom = null, $validTo = null)
    {
        $query = Planogram::query();

        if ($validFrom && $validTo) {
            $query->whereBetween('created_at', [$validFrom, $validTo]);
        } elseif ($validFrom) {
            $query->whereDate('created_at', '>=', $validFrom);
        } elseif ($validTo) {
            $query->whereDate('created_at', '<=', $validTo);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

     public function getMerchendishers()
    {
        $salesmanType = SalesmanType::where('salesman_type_name', 'Merchandiser')->first();

        if (!$salesmanType) {
            return collect(); 
        }
        return Salesman::where('type', $salesmanType->id)
                        ->select('id', 'osa_code', 'name')
                        ->get();
    }

    
public function getShelvesByCustomerIds(array $customerIds)
{
    $shelves = Shelve::select('id', 'shelf_name', 'code', 'customer_ids')
        ->whereNull('deleted_at')
        ->where(function ($query) use ($customerIds) {
            foreach ($customerIds as $customerId) {
                $query->orWhereJsonContains('customer_ids', $customerId);
            }
        })
        ->get();
    $groupedShelves = [];
    foreach ($shelves as $shelf) {
        $customerIdsString = implode(',', $shelf->customer_ids);
        if (!isset($groupedShelves[$customerIdsString])) {
            $groupedShelves[$customerIdsString] = [];
        }
        foreach ($shelf->customer_ids as $customerId) {
            $groupedShelves[$customerIdsString][] = [
                'id' => $shelf->id,
                'shelf_name' => $shelf->shelf_name,
                'code' => $shelf->code,
                'customer_ids' => $customerId,
            ];
        }
    }

    return $groupedShelves;
}

 public function getExportData(): Collection
{
    $records = DB::table('planogram_images as pi')
        ->join('planograms as p', 'pi.planogram_id', '=', 'p.id')
        ->leftJoin('salesman as m', 'pi.merchandiser_id', '=', 'm.id')
        ->leftJoin('tbl_company_customer as c', 'pi.customer_id', '=', 'c.id')
        ->leftJoin('shelves as s', 'pi.shelf_id', '=', 's.id')
        ->whereNull('pi.deleted_at')
        ->select([
            'p.id as planogram_id',
            'p.name as planogram_name',
            'p.code as planogram_code',
            'p.valid_from',
            'p.valid_to',
            'm.name as merchandiser_name',
            'c.business_name as customer_name',
            's.shelf_name as shelf_name',
            'pi.image',
        ])
        ->get();

    // Grouped by planogram
    $grouped = $records->groupBy('planogram_id')->map(function ($rows) {
        $first = $rows->first();

        $structuredImages = [];
        foreach ($rows as $row) {
            $mid = $row->merchandiser_name;
            $cid = $row->customer_name;
            $sid = $row->shelf_name;

            $structuredImages[$mid][$cid][] = [
                'shelf_name' => $sid,
                'image'      => $row->image,
            ];
        }

        return [
            'planogram_id'        => $first->planogram_id,
            'name'                => $first->planogram_name,
            'code'                => $first->planogram_code,
            'valid_from'          => $first->valid_from,
            'valid_to'            => $first->valid_to,
            'merchandiser_names'  => $rows->pluck('merchandiser_name')->filter()->unique()->values()->all(),
            'customer_names'      => $rows->pluck('customer_name')->filter()->unique()->values()->all(),
            'shelf_names'         => $rows->pluck('shelf_name')->filter()->unique()->values()->all(),
            'images'              => $structuredImages,
        ];
    });

    return $grouped->values();
}
    /**
     * Prepare flat rows for CSV/XLS export (tabular form).
     *
     * @return \Illuminate\Support\Collection
     */
   public function getFlatRows(): Collection
{
    $data = $this->getExportData();

    $flat = collect();
    foreach ($data as $plan) {
        $pid   = $plan['planogram_id'];
        $pname = $plan['name'];
        $pcode = $plan['code'];
        $vf    = $plan['valid_from'];
        $vt    = $plan['valid_to'];

        foreach ($plan['images'] as $mname => $custMap) {
            foreach ($custMap as $cname => $images) {
                foreach ($images as $img) {
                    $flat->push([
                        'planogram_id'      => $pid,
                        'planogram_name'    => $pname,
                        'planogram_code'    => $pcode,
                        'valid_from'        => $vf,
                        'valid_to'          => $vt,
                        'merchandiser_name' => $mname,
                        'customer_name'     => $cname,
                        'shelf_name'        => $img['shelf_name'] ?? null,
                        'image'             => $img['image'],
                    ]);
                }
            }
        }
    }

    return $flat;
}

}
