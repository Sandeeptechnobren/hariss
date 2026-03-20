<?php

namespace App\Swagger;

/**
 * @OA\Schema(
 *     schema="ApiResponseSuccess",
 *     type="object",
 *     @OA\Property(property="status", type="boolean", example=true),
 *     @OA\Property(property="code", type="integer", example=200),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object", nullable=true, example={"id": 1, "name": "Charara"}),
 *     @OA\Property(
 *         property="pagination",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="total", type="integer", example=100),
 *         @OA\Property(property="per_page", type="integer", example=10),
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=10)
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponseError",
 *     type="object",
 *     @OA\Property(property="status", type="boolean", example=false),
 *     @OA\Property(property="code", type="integer", example=400),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(property="errors", type="object", nullable=true, example={"email": {"The email field is required."}})
 * )
 * @OA\Schema(
 *     schema="Route",
 *     type="object",
 *     title="Route",
 *     description="Schema for creating or updating a route",
 *     required={"route_name", "warehouse_id", "route_type", "vehicle_id"},
 *
 *     @OA\Property(
 *         property="route_name",
 *         type="string",
 *         maxLength=50,
 *         example="RT004",
 *         description="Name of the route"
 *     ),
 *
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         example="Main delivery route across central region",
 *         description="Optional description of the route"
 *     ),
 *
 *     @OA\Property(
 *         property="warehouse_id",
 *         type="integer",
 *         example=1,
 *         description="Associated warehouse ID (must exist in tbl_warehouse)"
 *     ),
 *
 *     @OA\Property(
 *         property="route_type",
 *         type="integer",
 *         example=2,
 *         description="Route type ID (must exist in route_types)"
 *     ),
 *
 *     @OA\Property(
 *         property="vehicle_id",
 *         type="integer",
 *         example=5,
 *         description="Assigned vehicle ID (must exist in tbl_vehicle)"
 *     ),
 *
 *     @OA\Property(
 *         property="status",
 *         type="integer",
 *         enum={0,1},
 *         example=1,
 *         description="Route status: 0=Inactive, 1=Active"
 *     )
 * )
 */


class ApiResponseSchemas
{
    // This class is only for Swagger schema definitions
}
