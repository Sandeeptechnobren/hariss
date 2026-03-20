<?php

namespace App\Http\Controllers\V1\Master\Mob;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\V1\Master\Mob\SalesmanMobResource;
use App\Services\V1\MasterServices\Mob\SalesmanMobService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SalesmanMobController extends Controller
{
    protected $service;

    public function __construct(SalesmanMobService $loginService)
    {
        $this->service = $loginService;
    }

    /**
     * @OA\Post(
     *     path="/mob/master_mob/salesman/login",
     *     tags={"Salesman Authentication"},
     *     summary="Login API for salesman Mobile Api",
     *     description="Login by username, password and version check",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password","version"},
     *             @OA\Property(property="username", type="string", example="SMAN61"),
     *             @OA\Property(property="password", type="string", example="secret123"),
     *             @OA\Property(property="version", type="string", example="1.0")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials or version"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'version'  => 'required|string',
        ]);

        $response = $this->service->login(
            $request->username,
            $request->password,
            $request->version
        );

        if (!$response['status']) {
            return response()->json([
                'status' => false,
                'message' => $response['message'],
                'latest_version' => $response['latest_version'] ?? null
            ], 401);
        }

        $salesman = $response['data'];
        $files = $this->generateSalesmanFiles($salesman);

        return response()->json([
            'status' => true,
            'message' => $response['message'],
            'data' => new SalesmanMobResource($salesman),
            'customer_file_url' => $files['customer_file_url'] ?? null,
            'promotion_file_url' => $files['promotion_file_url'] ?? null,
            'discount_file_url' => $files['discount_file_url'] ?? null,
        ]);
    }

    protected function generateSalesmanFiles($salesman)
    {
        $files = [];

        $allFiles = Storage::disk('public')->files('salesman_files');
        $cutoff = now()->subDays(5)->timestamp;

        // Remove files older than 5 days
        foreach ($allFiles as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            if ($lastModified < $cutoff) {
                Storage::disk('public')->delete($file);
            }
        }

        $customerIds = [];
        if (!empty($salesman->route_id)) {
            $routeId = $salesman->route_id;

            $customers = \DB::table('tbl_customer')
                ->where('route_id', $routeId)
                ->whereNull('deleted_at')
                ->get();

            if ($customers->isNotEmpty()) {
                $customerIds = $customers->pluck('id')->toArray();

                $customerMapped = $customers->map(function ($c) use ($salesman) {
                    return [
                        'cust_id'            => (string)$c->id,
                        'salesman_id'        => (string)$salesman->id,
                        'cust_code'          => $c->osa_code,
                        'cust_name'          => $c->name,
                        'owner_name'         => $c->owner_name ?? '',
                        'payment_type'       => $c->payment_type ?? '1',
                        'creditlimit'        => $c->creditlimit ?? '0.00',
                        'balance'            => $c->balance ?? '0.00',
                        'longitude'          => $c->longitude ?? '',
                        'latitude'           => $c->latitude ?? '',
                        'barcode'            => $c->barcode ?? '',
                        'threshold_radius'   => $c->threshold_radius ?? '30',
                        'routeid'            => (string)$c->route_id,
                        'email'              => $c->email ?? '',
                        'division'           => $c->division ?? '0',
                        'address1'           => $c->address_1 ?? '',
                        'address2'           => $c->address_2 ?? '',
                        'region_id'          => $c->region_id ?? '',
                        'cust_phone'         => $c->phone_1 ?? '',
                        'customerzip'        => $c->customerzip ?? '',
                        'customercity'       => $c->city ?? '',
                        'customerstate'      => $c->state ?? '',
                        'outlet_channel'     => $c->outlet_channel_id ?? '0',
                        'is_fridge_assign'   => $c->is_fridge_assign ?? '0',
                        'Qr_value'           => $c->qr_value ?? '0',
                        'tin_no'             => $c->trn_no ?? '',
                    ];
                });

                $jsonContent = $customerMapped->toJson(JSON_UNESCAPED_UNICODE);
                $fileName = 'salesman_files/customers_' . now()->format('Ymd_His') . '.txt';
                Storage::disk('public')->put($fileName, $jsonContent);
                $files['customer_file_url'] = 'storage/' . $fileName;
            }
        }

        $route = \DB::table('tbl_route')->where('id', $salesman->route_id)->first();
        if ($route && !empty($route->warehouse_id)) {
            $warehouseId = $route->warehouse_id;

            $promotions = \DB::table('promotion_headers')
                ->where('warehouse_ids', $warehouseId)
                ->whereNull('deleted_at')
                ->get();

            if ($promotions->isNotEmpty()) {
                $promoMapped = $promotions->map(function ($p) {
                    return [
                        'promotion_id'   => (string)$p->id,
                        'promotion_name' => $p->promotion_name,
                        'start_date'     => $p->from_date,
                        'end_date'       => $p->to_date,
                        'warehouse_id'   => $p->warehouse_ids,
                    ];
                });

                $promoJson = $promoMapped->toJson(JSON_UNESCAPED_UNICODE);
                $fileName = 'salesman_files/promotions_' . now()->format('Ymd_His') . '.txt';
                Storage::disk('public')->put($fileName, $promoJson);
                $files['promotion_file_url'] = 'storage/' . $fileName;
            }
        }

        if (!empty($customerIds)) {
            $discounts = \DB::table('discounts')
                ->whereIn('customer_id', $customerIds)
                ->whereDate('start_date', '<=', now()->toDateString())
                ->whereDate('end_date', '>=', now()->toDateString())
                ->whereNull('deleted_at')
                ->get();

            if ($discounts->isNotEmpty()) {
                $discountMapped = $discounts->map(function ($d) {
                    return [
                        'customer_id'   => (string)$d->customer_id,
                        'discount_id'   => (string)$d->id,
                        'discount_type' => $d->discount_type ?? '',
                        'amount'        => $d->amount ?? '0',
                        'start_date'    => $d->start_date,
                        'end_date'      => $d->end_date,
                    ];
                });

                $discountJson = $discountMapped->toJson(JSON_UNESCAPED_UNICODE);
                $fileName = 'salesman_files/discounts_' . now()->format('Ymd_His') . '.txt';
                Storage::disk('public')->put($fileName, $discountJson);
                $files['discount_file_url'] = 'storage/' . $fileName;
            }
        }

        return $files;
    }
}
