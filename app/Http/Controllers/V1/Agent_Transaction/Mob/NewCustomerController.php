<?php

namespace App\Http\Controllers\V1\Agent_Transaction\Mob;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent_Transaction\Mob\NewCustomerRequest;
use App\Http\Requests\V1\Agent_Transaction\Mob\UpdateCustomerRequest;
use App\Http\Resources\V1\Agent_Transaction\Mob\NewCustomerResource;
use App\Models\Agent_Transaction\NewCustomer;
use App\Services\V1\Agent_Transaction\Mob\NewCustomerService;

// /**
//  * @OA\Schema(
//  *     schema="NewCustomer",
//  *     type="object", 
//  *     title="New Customer Schema",
//  *     required={"name", "customer_type", "route_id"},
//  *     @OA\Property(property="name", type="string", example="Ravi Traders"),
//  *     @OA\Property(property="customer_type", type="integer", example=1),
//  *     @OA\Property(property="route_id", type="integer", example=2),
//  *     @OA\Property(property="contact_no", type="string", example="9876543210"),
//  *     @OA\Property(property="whatsapp_no", type="string", example="9876543210"),
//  *     @OA\Property(property="buyertype", type="string", example="Retail"),
//  *     @OA\Property(property="town", type="string", example="Pune"),
//  *     @OA\Property(property="district", type="string", example="Maharashtra"),
//  *     @OA\Property(property="status", type="string", example="Active"),
//  *     @OA\Property(property="osa_code", type="string", example="NC0001")
//  * )
//  */
class NewCustomerController extends Controller
{
    protected $service;

    public function __construct(NewCustomerService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/mob/master_mob/new_customers/list",
     *     tags={"New Customer Mob"},
     *     summary="Get all new customers",
     *     @OA\Response(
     *         response=200,
     *         description="Customer list fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer list fetched successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/NewCustomer"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $customers = $this->service->getAll();

        return response()->json([
            'status' => true,
            'message' => 'Customer list fetched successfully',
            'data' => NewCustomerResource::collection($customers)
        ]);
    }

    /**
     * @OA\Get(
     *     path="/mob/master_mob/new_customers/{uuid}",
     *     tags={"New Customer Mob"},
     *     summary="Get a single new customer by UUID",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Customer UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer data fetched successfully",
     *         @OA\JsonContent(ref="#/components/schemas/NewCustomer")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
    public function show($uuid)
    {
        $customer = $this->service->getById($uuid);

        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => new NewCustomerResource($customer)
        ]);
    }

    /**
     * @OA\Post(
     *     path="/mob/master_mob/new_customers/add",
     *     tags={"New Customer Mob"},
     *     summary="Create a new customer",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/NewCustomer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Customer created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/NewCustomer")
     *         )
     *     )
     * )
     */
    public function store(NewCustomerRequest $request)
    {
        $data = $request->validated();
        $customer = NewCustomer::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }
 /**
     * @OA\Put(
     *     path="/mob/master_mob/new_customers/update/{uuid}",
     *     tags={"New Customer Mob"},
     *     summary="Update a customer",
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Customer UUID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Customer update payload (all fields optional)",
     *         @OA\JsonContent(ref="#/components/schemas/NewCustomer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/NewCustomer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Customer not found"
     *     )
     * )
     */
     public function update(NewCustomerRequest $request, $uuid)
    {
        $customer = NewCustomer::where('uuid', $uuid)->first();

        if (!$customer) {
            return response()->json([
                'status' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $updated = $this->service->update($customer, $request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Customer updated successfully',
            'data' => new NewCustomerResource($updated)
        ]);
    }
/**
 * @OA\Put(
 *     path="/mob/master_mob/new_customers/edit/{uuid}",
 *     tags={"New Customer Mob"},
 *     summary="Update customer by UUID",
 *     description="Updates customer details using customer UUID. Only provided fields will be updated.",
 *
 *     @OA\Parameter(
 *         name="uuid",
 *         in="path",
 *         required=true,
 *         description="Customer UUID",
 *         @OA\Schema(
 *             type="string",
 *             format="uuid",
 *             example="9d1f8c12-9f22-4e91-b99f-8899abcd1234"
 *         )
 *     ),
 *
 *     @OA\RequestBody(
 *         required=true,
 *         description="Customer update payload (partial update allowed)",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="name", type="string", example="ABC Store"),
 *             @OA\Property(property="customer_type", type="integer", example=1),
 *             @OA\Property(property="warehouse", type="integer", example=2),
 *             @OA\Property(property="owner_name", type="string", example="Ramesh Kumar"),
 *             @OA\Property(property="route_id", type="integer", example=5),
 *             @OA\Property(property="landmark", type="string", example="Near Main Road"),
 *             @OA\Property(property="district", type="string", example="Indore"),
 *             @OA\Property(property="street", type="string", example="MG Road"),
 *             @OA\Property(property="town", type="string", example="Vijay Nagar"),
 *             @OA\Property(property="whatsapp_no", type="string", example="919876543210"),
 *             @OA\Property(property="contact_no", type="string", example="9876543210"),
 *             @OA\Property(property="contact_no2", type="string", example="9123456789"),
 *             @OA\Property(property="buyertype", type="integer", example=1),
 *             @OA\Property(property="payment_type", type="integer", example=2),
 *             @OA\Property(property="is_cash", type="integer", example=1),
 *             @OA\Property(property="vat_no", type="string", example="VAT12345"),
 *             @OA\Property(property="creditday", type="integer", example=30),
 *             @OA\Property(property="credit_limit", type="number", example=50000),
 *             @OA\Property(property="outlet_channel_id", type="integer", example=3),
 *             @OA\Property(property="category_id", type="integer", example=1),
 *             @OA\Property(property="subcategory_id", type="integer", example=4),
 *             @OA\Property(property="latitude", type="string", example="22.7196"),
 *             @OA\Property(property="longitude", type="string", example="75.8577"),
 *             @OA\Property(property="qr_code", type="string", example="QR123ABC"),
 *             @OA\Property(property="status", type="integer", example=1),
 *             @OA\Property(property="enable_promotion", type="integer", example=1)
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Customer updated successfully"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Customer not found"
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     )
 * )
 */

public function updatecustomer(UpdateCustomerRequest $request, string $uuid)
{
    $customer = $this->service->updateByUuid(
        $uuid,
        $request->validated()
    );
    return response()->json([
        'status'  => true,
        'code'    => 200,
        'message' => 'Customer updated successfully',
        'data'    => $customer,
    ]);
}

}

