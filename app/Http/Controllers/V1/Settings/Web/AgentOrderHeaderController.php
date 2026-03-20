<?php

namespace App\Http\Controllers\V1\Settings\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Settings\Web\AgentOrderHeaderRequest;
use App\Http\Resources\V1\Settings\Web\AgentOrderHeaderResource;
use App\Services\V1\Settings\Web\AgentOrderHeaderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

// /**
//  * @OA\Schema(
//  *     schema="AgentOrderHeader",
//  *     type="object",
//  *     title="Agent Order Header",
//  *     required={"order_number", "order_date"},
//  *     @OA\Property(property="agent_id", type="integer", example=1),
//  *     @OA\Property(property="sap_order_id", type="string", example="SAP123456"),
//  *     @OA\Property(property="order_number", type="string", example="ORD-001"),
//  *     @OA\Property(property="order_date", type="string", format="date", example="2025-09-18"),
//  *     @OA\Property(property="delivery_date", type="string", format="date", example="2025-09-20"),
//  *     @OA\Property(property="payment_term", type="string", example="30 days"),
//  *     @OA\Property(property="price_list_id", type="integer", example=2),
//  *     @OA\Property(property="currency", type="string", example="USD"),
//  *     @OA\Property(property="gross_total", type="number", format="float", example=1000.50),
//  *     @OA\Property(property="excise", type="number", format="float", example=50.00),
//  *     @OA\Property(property="vat", type="number", format="float", example=120.00),
//  *     @OA\Property(property="pre_vat", type="number", format="float", example=880.50),
//  *     @OA\Property(property="discount", type="number", format="float", example=20.00),
//  *     @OA\Property(property="net_total", type="number", format="float", example=860.50),
//  *     @OA\Property(property="total_amount", type="number", format="float", example=980.50),
//  *     @OA\Property(property="order_status", type="string", enum={"DRAFT","PENDING","APPROVED","REJECTED","CANCELLED"}, example="DRAFT"),
//  *     @OA\Property(property="reject_reason", type="string", example="Stock not available"),
//  *     @OA\Property(property="order_comment", type="string", example="Urgent delivery requested"),
//  *     @OA\Property(property="sales_backoffice_comment", type="string", example="Check stock before approval"),
//  *     @OA\Property(property="signature_img", type="string", example="uploads/signatures/order_001.png"),
//  *     @OA\Property(property="sap_return_message", type="string", example="SAP order created successfully"),
//  *     @OA\Property(property="is_delivered", type="boolean", example=false),
//  *     @OA\Property(property="status", type="boolean", example=true),
//  *     @OA\Property(property="created_by", type="integer", example=5),
//  *     @OA\Property(property="updated_by", type="integer", example=7),
//  * )
//  */
class AgentOrderHeaderController extends Controller
{
    use ApiResponse;

    protected AgentOrderHeaderService $service;

    public function __construct(AgentOrderHeaderService $service)
    {
        $this->service = $service;
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/settings/agent_order_header/list",
    //  *     summary="List all agent orders",
    //  *     tags={"Agent Order Header"},
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Success",
    //  *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/AgentOrderHeader"))
    //  *     )
    //  * )
    //  */
    public function index(): JsonResponse
    {
        return $this->success(
            AgentOrderHeaderResource::collection($this->service->list())
        );
    }

    // /**
    //  * @OA\Post(
    //  *     path="/api/settings/agent_order_header/create",
    //  *     summary="Create a new agent order",
    //  *     tags={"Agent Order Header"},
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(ref="#/components/schemas/AgentOrderHeader")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=201,
    //  *         description="Order created successfully",
    //  *         @OA\JsonContent(ref="#/components/schemas/AgentOrderHeader")
    //  *     )
    //  * )
    //  */
    public function store(AgentOrderHeaderRequest $request): JsonResponse
    {
        $order = $this->service->create($request->validated());
        return $this->success(new AgentOrderHeaderResource($order), 'Order created successfully');
    }

    // /**
    //  * @OA\Get(
    //  *     path="/api/settings/agent_order_header/{id}",
    //  *     summary="Get agent order details",
    //  *     tags={"Agent Order Header"},
    //  *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Success",
    //  *         @OA\JsonContent(ref="#/components/schemas/AgentOrderHeader")
    //  *     ),
    //  *     @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function show(int $id): JsonResponse
    {
        $order = $this->service->show($id);
        return $this->success(new AgentOrderHeaderResource($order));
    }

    // /**
    //  * @OA\Put(
    //  *     path="/api/settings/agent_order_header/{id}/update",
    //  *     summary="Update an agent order",
    //  *     tags={"Agent Order Header"},
    //  *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *     @OA\RequestBody(
    //  *         required=true,
    //  *         @OA\JsonContent(ref="#/components/schemas/AgentOrderHeader")
    //  *     ),
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Order updated successfully",
    //  *         @OA\JsonContent(ref="#/components/schemas/AgentOrderHeader")
    //  *     ),
    //  *     @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function update(AgentOrderHeaderRequest $request, int $id): JsonResponse
    {
        $order = $this->service->update($id, $request->validated());
        return $this->success(new AgentOrderHeaderResource($order), 'Order updated successfully');
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/settings/agent_order_header/{id}/delete",
    //  *     summary="Delete an agent order",
    //  *     tags={"Agent Order Header"},
    //  *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    //  *     @OA\Response(response=200, description="Order deleted successfully"),
    //  *     @OA\Response(response=404, description="Not Found")
    //  * )
    //  */
    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return $this->success(null, 'Order deleted successfully');
    }
}
