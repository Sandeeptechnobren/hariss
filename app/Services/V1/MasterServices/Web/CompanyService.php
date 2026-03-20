<?php

namespace App\Services\V1\MasterServices\Web;

use App\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\V1\MasterRequests\Web\CompanyRequest;
use App\Traits\ApiResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    use ApiResponse;

    // public function getAll($perPage = 10, $filters = [])
    // {
    //     try {
    //         $query = Company::with('country')->orderByDesc('id');
    //         // foreach ($filters as $field => $value) {
    //         //     if (!empty($value)) {
    //         //         if (in_array($field, ['company_name', 'email', 'tin_number', 'vat', 'selling_currency', 'purchase_currency', 'toll_free_no', 'website', 'district', 'town', 'street', 'landmark', 'region', 'sub_region', 'primary_contact'])) {
    //         //             $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //         //         } else {
    //         //             $query->where($field, $value);
    //         //         }
    //         //     }
    //         // }
    //         foreach ($filters as $field => $value) {
    //             if (!empty($value)) {
    //                 $query->whereRaw("LOWER({$field}) LIKE ?", ['%' . strtolower($value) . '%']);
    //             }
    //         }
    //         return $query->paginate($perPage);
    //     } catch (Exception $e) {
    //         throw new Exception("Failed to fetch companies: " . $e->getMessage());
    //     }
    // }

public function getAll(
    int $perPage = 10,
    array $filters = [],
    bool $dropdown = false
) {
    try {
        $query = Company::query()->orderByDesc('id');
        if (array_key_exists('status', $filters) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 1);
        }

        if ($dropdown) {
            return $query
                ->select('id', 'company_name', 'company_code', 'status')
                ->orderBy('company_name')
                ->get();
        }
        $query->with('country');

        foreach ($filters as $field => $value) {
            if ($value === '' || $value === null || $field === 'status') {
                continue;
            }
            $query->whereRaw(
                "LOWER({$field}) LIKE ?",
                ['%' . strtolower($value) . '%']
            );
        }

        return $query->paginate($perPage);

    } catch (Exception $e) {
        throw new Exception(
            "Failed to fetch companies: " . $e->getMessage()
        );
    }
}


    public function search($perPage = 10, $keyword = null)
    {
        try {
            $query = Company::with(['country:id,country_name,country_code']);

            if (!empty($keyword)) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('company_name', 'like', "%{$keyword}%")
                        ->orWhere('company_code', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('tin_number', 'like', "%{$keyword}%")
                        ->orWhere('vat', 'like', "%{$keyword}%")
                        ->orWhere('selling_currency', 'like', "%{$keyword}%")
                        ->orWhere('purchase_currency', 'like', "%{$keyword}%")
                        ->orWhere('toll_free_no', 'like', "%{$keyword}%")
                        ->orWhere('website', 'like', "%{$keyword}%")
                        ->orWhere('service_type', 'like', "%{$keyword}%")
                        ->orWhere('company_type', 'like', "%{$keyword}%")
                        ->orWhere('address', 'like', "%{$keyword}%")
                        ->orWhere('primary_contact', 'like', "%{$keyword}%");
                });
            }

            return $query->paginate($perPage);
        } catch (Exception $e) {
            throw new Exception("Failed to search companies: " . $e->getMessage());
        }
    }
    // public function search($perPage = 10, $keyword = null)
    // {
    //     try {
    //         $query = Company::with(['country:id,country_name,country_code']);

    //         if (!empty($keyword)) {
    //             $query->where(function ($q) use ($keyword) {
    //                 $q->where('company_name', 'like', "%{$keyword}%")
    //                     ->orWhere('company_code', 'like', "%{$keyword}%")
    //                     ->orWhere('email', 'like', "%{$keyword}%")
    //                     ->orWhere('tin_number', 'like', "%{$keyword}%")
    //                     ->orWhere('vat', 'like', "%{$keyword}%")
    //                     ->orWhere('selling_currency', 'like', "%{$keyword}%")
    //                     ->orWhere('purchase_currency', 'like', "%{$keyword}%")
    //                     ->orWhere('toll_free_no', 'like', "%{$keyword}%")
    //                     ->orWhere('website', 'like', "%{$keyword}%")
    //                     ->orWhere('service_type', 'like', "%{$keyword}%")
    //                     ->orWhere('company_type', 'like', "%{$keyword}%")
    //                     // ->orWhere('district', 'like', "%{$keyword}%")
    //                     ->orWhere('town', 'like', "%{$keyword}%")
    //                     ->orWhere('address', 'like', "%{$keyword}%")
    //                     // ->orWhere('landmark', 'like', "%{$keyword}%")
    //                     ->orWhere('primary_contact', 'like', "%{$keyword}%");
    //             });
    //         }

    //         return $query->paginate($perPage);
    //     } catch (Exception $e) {
    //         throw new Exception("Failed to search companies: " . $e->getMessage());
    //     }
    // }


    public function findById($id)
    {
        try {
            return Company::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new Exception("Company not found with id: {$id}");
        } catch (Exception $e) {
            throw new Exception("Failed to fetch company: " . $e->getMessage());
        }
    }
    // public function create($data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         if (empty($data['company_code'])) {
    //             $data['company_code'] = Company::generateCode();
    //         }
    //         $company = Company::create($data);
    //         DB::commit();
    //         return $company->fresh();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Failed to create company', ['data' => $data, 'exception' => $e]);
    //         throw new Exception("Failed to create company: " . $e->getMessage());
    //     }
    // }
    public function create($data)
    {
        DB::beginTransaction();
        try {
            if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                $path = $data['logo']->store('logos', 'public'); // stored in storage/app/public/logos
                $data['logo'] = $path;
            }
            if (empty($data['company_code'])) {
                $data['company_code'] = Company::generateCode();
            }
            $company = Company::create($data);

            DB::commit();
            if ($company->logo) {
                $company->logo_url = asset('storage/app/public/' . $company->logo);
            }
            return $company->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create company', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            throw new \Exception("Failed to create company: " . $e->getMessage());
        }
    }

    // public function update(Company $company, array $data)
    // {
    //     try {
    //         $company->update($data);
    //         return $company;
    //     } catch (Exception $e) {
    //         throw new Exception("Failed to update company: " . $e->getMessage());
    //     }
    // }

    public function update(Company $company, array $data)
    {
        DB::beginTransaction();
        try {
            if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
                if (!empty($company->logo) && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }
                $path = $data['logo']->store('logos', 'public');
                $data['logo'] = $path;
            }
            $company->update($data);
            // dd($company);
            DB::commit();
            if ($company->logo) {
                $company->logo_url = asset('storage/app/public/' . $company->logo);
            }
            return $company->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update company', [
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            throw new \Exception("Failed to update company: " . $e->getMessage());
        }
    }
    // public function update(Company $company, array $data)
    // {
    //     DB::beginTransaction();
    //     try {
    //         // Handle logo upload
    //         if (isset($data['logo']) && $data['logo'] instanceof \Illuminate\Http\UploadedFile) {
    //             $data['logo'] = $data['logo']->store('logos', 'public');
    //         }

    //         // Update the company
    //         $company->update($data);

    //         DB::commit();

    //         // Add logo URL
    //         if ($company->logo) {
    //             $company->logo_url = asset('storage/' . $company->logo);
    //         }

    //         return $company->fresh();

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw new \Exception("Failed to update company: " . $e->getMessage());
    //     }
    // }

    public function delete(int $id): ?Company
    {
        DB::beginTransaction();

        try {
            $company = Company::find($id);

            if (!$company) {
                return null;
            }

            $company->delete();

            DB::commit();
            return $company; // return deleted company object (or null)
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete company: " . $e->getMessage(), ['company_id' => $id]);
            throw new Exception("Failed to delete company: " . $e->getMessage());
        }
    }
    public function bulkUpload($file)
    {
        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            if (empty($sheetData) || count($sheetData) < 2) {
                throw new \Exception("Excel file is empty or invalid.");
            }
            $header = array_map('strtolower', array_map('trim', $sheetData[1]));
            unset($sheetData[1]);
            $expectedHeaders = [
                'company_name',
                'email',
                'tin_number',
                'vat',
                'selling_currency',
                'purchase_currency',
                'toll_free_no',
                'website',
                'district',
                'town',
                'street',
                'landmark',
                'region_id',
                'sub_region_id',
                'primary_contact',
                'country_id',
                'service_type',
                'company_type',
                'status'
            ];
            foreach ($expectedHeaders as $expected) {
                if (!in_array($expected, $header)) {
                    throw new \Exception("Missing required header: {$expected}");
                }
            }
            $insertData = [];
            foreach ($sheetData as $rowNumber => $row) {
                $row = array_map('trim', $row);
                $data = array_combine($header, array_values($row));
                if (!array_filter($data)) continue; // skip empty rows
                $requiredFields = [
                    'company_name',
                    'email',
                    'tin_number',
                    'vat',
                    'selling_currency',
                    'purchase_currency',
                    'toll_free_no',
                    'website',
                    'city',
                    'address',
                    'primary_contact',
                    'country_id',
                    'service_type',
                    'company_type',
                    'status'
                ];
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        throw new \Exception("Row {$rowNumber} is missing required field: {$field}");
                    }
                }
                if (Company::where('email', $data['email'])->exists()) {
                    throw new \Exception("Row {$rowNumber}: Email '{$data['email']}' already exists.");
                }
                if (Company::where('tin_number', $data['tin_number'])->exists()) {
                    throw new \Exception("Row {$rowNumber}: TIN Number '{$data['tin_number']}' already exists.");
                }
                if (Company::where('vat', $data['vat'])->exists()) {
                    throw new \Exception("Row {$rowNumber}: VAT '{$data['vat']}' already exists.");
                }
                if (!in_array(strtolower($data['status']), ['active', 'inactive'])) {
                    throw new \Exception("Row {$rowNumber}: Status must be 'active' or '    inactive'.");
                }
                $insertData[] = [
                    'company_name' => $data['company_name'],
                    'company_code' => Company::generateCode(),
                    'email' => $data['email'],
                    'tin_number' => $data['tin_number'],
                    'vat' => $data['vat'],
                    'selling_currency' => $data['selling_currency'],
                    'purchase_currency' => $data['purchase_currency'],
                    'toll_free_no' => $data['toll_free_no'],
                    'website' => $data['website'],
                    // 'district' => $data['district'],
                    // 'town' => $data['town'],
                    'city' => $data['street'],
                    'address' => $data['address'],
                    // 'region_id' => $data['region_id'],
                    // 'sub_region_id' => $data['sub_region_id'],
                    'primary_contact' => $data['primary_contact'],
                    'country_id' => $data['country_id'],
                    'service_type' => $data['service_type'],
                    'company_type' => $data['company_type'],
                    'status' => strtolower($data['status']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $insertData[] = Company::create($data);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Bulk upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
