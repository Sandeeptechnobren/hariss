<?php
namespace App\Services\V1\Merchendisher\Web;

use App\Models\Planogram;
use App\Models\Salesman;
use App\Models\SalesmanType;
use App\Models\Shelve;
use App\Models\PlanogramImage;
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

    if (!empty($search)) {
        $s = strtolower($search);

        $query->where(function ($q) use ($s) {

            $q->orWhereRaw("LOWER(CAST(id AS TEXT)) LIKE ?", ["%{$s}%"])
              ->orWhereRaw("LOWER(name) LIKE ?", ["%{$s}%"])
              ->orWhereRaw("LOWER(CAST(valid_from AS TEXT)) LIKE ?", ["%{$s}%"])
              ->orWhereRaw("LOWER(CAST(valid_to AS TEXT)) LIKE ?", ["%{$s}%"]);
        });
    }

    return $query->paginate(request()->get('per_page', 50));
}
    public function getByuuid($uuid): ?Planogram
    {
        return Planogram::where('uuid', $uuid)->first();
    }

public function store(array $data): Planogram
{
    return DB::transaction(function () use ($data) {

        // Handle images
        $imagePaths = [];
        if (request()->hasFile('images')) {
            $images = request()->file('images');
            if (!is_array($images)) {
                $images = [$images]; 
            }
            foreach ($images as $image) {
                $path = $image->store('planogram_images', 'public');
                $imagePaths[] = Storage::url($path);
            }
        }
        $merchandisherIds = is_array($data['merchendisher_id'])
            ? implode(',', $data['merchendisher_id'])
            : $data['merchendisher_id'];
        $customerIds = is_array($data['customer_id'])
            ? implode(',', $data['customer_id'])
            : $data['customer_id'];
        $imagesString = !empty($imagePaths) ? implode(',', $imagePaths) : null;
        return Planogram::create([
            'name'              => $data['name'],
            'valid_from'        => $data['valid_from'],
            'valid_to'          => $data['valid_to'],
            'merchendisher_id'  => $merchandisherIds,
            'customer_id'       => $customerIds,
            'images'            => $imagesString,
        ]);
    });
}
public function update(Planogram $planogram, array $data): Planogram
{
    return DB::transaction(function () use ($planogram, $data) {
        $merchandisherIds = array_key_exists('merchendisher_id', $data)
            ? implode(',', (array) $data['merchendisher_id'])
            : $planogram->merchendisher_id;
        $customerIds = array_key_exists('customer_id', $data)
            ? implode(',', (array) $data['customer_id'])
            : $planogram->customer_id;
        $imagePaths = null;
        if (request()->hasFile('images')) {
            if (!empty($planogram->images)) {
                foreach (explode(',', $planogram->images) as $oldImage) {
                    $oldPath = str_replace('/storage/', '', $oldImage);
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $imagePaths = [];
            foreach (request()->file('images') as $image) {
                $path = $image->store('planogram_images', 'public');
                $imagePaths[] = Storage::url($path);
            }
        }
        $planogram->update([
            'name'             => $data['name'] ?? $planogram->name,
            'code'             => $data['code'] ?? $planogram->code,
            'valid_from'       => $data['valid_from'] ?? $planogram->valid_from,
            'valid_to'         => $data['valid_to'] ?? $planogram->valid_to,
            'merchendisher_id' => $merchandisherIds,
            'customer_id'      => $customerIds,
            'images'           => $imagePaths !== null
                ? implode(',', $imagePaths)
                : $planogram->images,
        ]);

        $planogram->refresh();
        return $planogram;
    });
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
        return Salesman::where('sub_type', 6)
                        ->select('id', 'osa_code', 'name')
                        ->get();
    }

    // public function getShelvesByCustomerIds(array $customerIds)
    // {
    //     return Shelve::select('id', 'shelf_name', 'code')
    //         ->whereNull('deleted_at')
    //         ->where(function ($query) use ($customerIds) {
    //             foreach ($customerIds as $customerId) {
    //                 $query->orWhereJsonContains('customer_ids', $customerId);
    //             }
    //         })
    //         ->get();
    // }

   public function getShelvesByCustomerGroups(array $customerGroups)
{
    $allCustomerIds = collect($customerGroups)->flatten()->unique()->values()->all();

    $shelves = Shelve::select('id', 'shelf_name', 'code', 'customer_ids')
        ->whereNull('deleted_at')
        ->where(function ($query) use ($allCustomerIds) {
            foreach ($allCustomerIds as $customerId) {
                $query->orWhereJsonContains('customer_ids', $customerId);
            }
        })
        ->get();
    $response = [];

    foreach ($customerGroups as $merchandiserId => $customerIds) {
        $response[$merchandiserId] = [];

        foreach ($customerIds as $customerId) {
            $matchingShelves = $shelves->filter(function ($shelf) use ($customerId) {
                return in_array($customerId, $shelf->customer_ids);
            })->map(function ($shelf) {
                return [
                    'shelf_id' => $shelf->id,
                    'shelf_name' => $shelf->shelf_name,
                    'code' => $shelf->code,
                ];
            })->values();

            $response[$merchandiserId][$customerId] = $matchingShelves;
        }
    }

    return $response;
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
